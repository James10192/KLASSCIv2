<?php

namespace App\Services\Scoring\Calculators;

use App\Models\User;
use App\Services\Scoring\Concerns\ScoresDatabaseActivity;
use App\Services\Scoring\PersonnelScoreResult;
use Carbon\CarbonInterface;

class AdministrativeScoringCalculator
{
    use ScoresDatabaseActivity;

    public function calculate(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        return match ($dimension) {
            'administrative_activity' => $this->administrative($dimension, $definition, $user, $start, $end),
            'communication_activity' => $this->communication($dimension, $definition, $user, $start, $end),
            'platform_activity' => $this->platform($dimension, $definition, $user, $start, $end),
            default => new PersonnelScoreResult($dimension, $definition['label'], 0, $definition['weight'] ?? 0),
        };
    }

    private function administrative(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $students = $this->countRows('esbtp_etudiants', $start, $end, function ($query) use ($user) {
            $this->whereActorColumns($query, 'esbtp_etudiants', $user);
        });
        $inscriptions = $this->countRows('esbtp_inscriptions', $start, $end, function ($query) use ($user) {
            $this->whereActorColumns($query, 'esbtp_inscriptions', $user);
        });

        $score = min(100, $this->scoreByTarget($students, 10) * 0.45 + $this->scoreByTarget($inscriptions, 10) * 0.55);

        return new PersonnelScoreResult($dimension, $definition['label'], (int) round($score), $definition['weight'], [
            'etudiants_traites' => $students,
            'inscriptions_traitees' => $inscriptions,
        ]);
    }

    private function communication(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $annonces = $this->countRows('announcements', $start, $end, function ($query) use ($user) {
            if ($this->columnExists('announcements', 'created_by')) {
                $query->where('created_by', $user->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        });
        $messages = $this->countRows('messages', $start, $end, function ($query) use ($user) {
            if ($this->columnExists('messages', 'sender_id')) {
                $query->where('sender_id', $user->id);
            } elseif ($this->columnExists('messages', 'created_by')) {
                $query->where('created_by', $user->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        });
        $score = min(100, $this->scoreByTarget($annonces, 2) * 0.45 + $this->scoreByTarget($messages, 10) * 0.55);

        return new PersonnelScoreResult($dimension, $definition['label'], (int) round($score), $definition['weight'], [
            'annonces' => $annonces,
            'messages' => $messages,
        ]);
    }

    private function platform(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $lastSeen = $user->last_seen_at ?: $user->last_login_at;
        $score = 0;
        if ($lastSeen) {
            $days = $lastSeen->diffInDays(now());
            $score = $days <= 1 ? 100 : ($days <= 7 ? 75 : ($days <= 30 ? 40 : 10));
        }

        return new PersonnelScoreResult($dimension, $definition['label'], $score, $definition['weight'], [
            'derniere_activite' => $lastSeen?->toDateTimeString(),
        ]);
    }

    private function whereActorColumns($query, string $table, User $user): void
    {
        if ($this->columnExists($table, 'created_by') && $this->columnExists($table, 'updated_by')) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)->orWhere('updated_by', $user->id);
            });
            return;
        }

        if ($this->columnExists($table, 'created_by')) {
            $query->where('created_by', $user->id);
            return;
        }

        if ($this->columnExists($table, 'updated_by')) {
            $query->where('updated_by', $user->id);
            return;
        }

        $query->whereRaw('1 = 0');
    }
}
