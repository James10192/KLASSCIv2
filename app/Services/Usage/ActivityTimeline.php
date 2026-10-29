<?php

namespace App\Services\Usage;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Transforme les lignes (origine, compte, jour, evenement) en rythme
 * d'activite : par groupe d'acteurs, par jour, par semaine, par compte.
 *
 * Une « journee active » est un jour ou un compte a enregistre au moins une
 * ecriture. Les consultations de pages ne laissent pas de trace, seules
 * les lectures de paiements et de factures sont journalisees : elles sont
 * comptees a part, jamais melees aux ecritures.
 */
class ActivityTimeline
{
    private array $writeEvents;

    public function __construct(
        private readonly UsageWindow $window,
        private readonly UsageActorDirectory $actors,
        private readonly int $bulkThreshold,
    ) {
        $this->writeEvents = config('usage_report.write_events');
    }

    public function build(Collection $rows): array
    {
        $buckets = [];
        $userDays = [];   // [bucket][userId][day] => writes
        $reads = [];      // [bucket] => lectures

        foreach ($rows as $row) {
            $bucket = $this->bucketOf($row);
            $buckets[$bucket] ??= ['actions' => 0, 'lectures' => 0];

            if (in_array($row->event, $this->writeEvents, true)) {
                $buckets[$bucket]['actions'] += (int) $row->total;
                if ($row->user_id !== null) {
                    $userDays[$bucket][(int) $row->user_id][$row->day] =
                        ($userDays[$bucket][(int) $row->user_id][$row->day] ?? 0) + (int) $row->total;
                }
            } else {
                $buckets[$bucket]['lectures'] += (int) $row->total;
                if ($row->user_id !== null) {
                    $reads[$bucket][(int) $row->user_id] = true;
                }
            }
        }

        foreach ($buckets as $bucket => &$totals) {
            $totals['comptes_actifs'] = count($userDays[$bucket] ?? []);
            $totals['comptes_lecteurs'] = count($reads[$bucket] ?? []);
        }

        $school = $userDays[UsageActorDirectory::ECOLE] ?? [];

        return [
            'groupes' => $buckets,
            'jours' => $this->daily($school),
            'semaines' => $this->weekly($school),
            'mois' => $this->monthly($school),
            'comptes' => $this->perUser($school),
            'journees_de_masse' => $this->bulkDays($userDays),
        ];
    }

    private function bucketOf(object $row): string
    {
        return match ($row->origin) {
            AuditActivityQuery::ORIGIN_TOOL => UsageActorDirectory::KLASSCI,
            AuditActivityQuery::ORIGIN_SYSTEM => UsageActorDirectory::SYSTEME,
            default => $this->actors->bucket((int) $row->user_id),
        };
    }

    /** Une ligne par jour de la periode, jours vides compris. */
    private function daily(array $school): array
    {
        $days = [];
        for ($d = $this->window->from; $d->lessThanOrEqualTo($this->window->to); $d = $d->addDay()) {
            $days[$d->toDateString()] = ['jour' => $d->toDateString(), 'actions' => 0, 'comptes' => 0];
        }
        foreach ($school as $perDay) {
            foreach ($perDay as $day => $count) {
                if (isset($days[$day])) {
                    $days[$day]['actions'] += $count;
                    $days[$day]['comptes']++;
                }
            }
        }

        return array_values($days);
    }

    private function weekly(array $school): array
    {
        $weeks = [];
        foreach ($this->window->weekStarts() as $start) {
            $weeks[$start] = ['semaine' => $start, 'actions' => 0, 'actions_hors_masse' => 0, 'comptes' => []];
        }
        foreach ($school as $userId => $perDay) {
            foreach ($perDay as $day => $count) {
                $week = CarbonImmutable::parse($day)->startOfWeek()->toDateString();
                if (!isset($weeks[$week])) {
                    continue;
                }
                $weeks[$week]['actions'] += $count;
                $weeks[$week]['actions_hors_masse'] += $count > $this->bulkThreshold ? 0 : $count;
                $weeks[$week]['comptes'][$userId] = true;
            }
        }

        return array_values(array_map(fn ($w) => [...$w, 'comptes' => count($w['comptes'])], $weeks));
    }

    /** Par mois : ecritures, comptes distincts, jours distincts avec activite. */
    private function monthly(array $school): array
    {
        $months = [];
        foreach ($school as $userId => $perDay) {
            foreach ($perDay as $day => $count) {
                $month = substr($day, 0, 7);
                $months[$month] ??= ['mois' => $month, 'actions' => 0, 'comptes' => [], 'jours' => []];
                $months[$month]['actions'] += $count;
                $months[$month]['comptes'][$userId] = true;
                $months[$month]['jours'][$day] = true;
            }
        }
        for ($m = $this->window->from->startOfMonth(); $m->lessThanOrEqualTo($this->window->to); $m = $m->addMonth()) {
            $months[$m->format('Y-m')] ??= ['mois' => $m->format('Y-m'), 'actions' => 0, 'comptes' => [], 'jours' => []];
        }
        ksort($months);

        return array_values(array_map(fn ($m) => [...$m, 'comptes' => count($m['comptes']), 'jours' => count($m['jours'])], $months));
    }

    private function perUser(array $school): array
    {
        $users = [];
        foreach ($school as $userId => $perDay) {
            ksort($perDay);
            $users[] = [
                'user_id' => $userId,
                'role' => $this->actors->role($userId),
                'actions' => array_sum($perDay),
                'jours_actifs' => count($perDay),
                'jours_de_masse' => count(array_filter($perDay, fn ($c) => $c > $this->bulkThreshold)),
                'premiere_action' => array_key_first($perDay),
                'derniere_action' => array_key_last($perDay),
            ];
        }
        usort($users, fn ($a, $b) => [$b['jours_actifs'], $b['actions']] <=> [$a['jours_actifs'], $a['actions']]);

        return $users;
    }

    private function bulkDays(array $userDays): array
    {
        $events = [];
        foreach ($userDays as $bucket => $users) {
            foreach ($users as $userId => $perDay) {
                foreach ($perDay as $day => $count) {
                    if ($count > $this->bulkThreshold) {
                        $events[] = ['groupe' => $bucket, 'user_id' => $userId, 'role' => $this->actors->role($userId), 'jour' => $day, 'actions' => $count];
                    }
                }
            }
        }
        usort($events, fn ($a, $b) => $b['actions'] <=> $a['actions']);

        return $events;
    }
}
