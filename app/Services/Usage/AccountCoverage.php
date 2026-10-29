<?php

namespace App\Services\Usage;

use Illuminate\Support\Facades\DB;

/**
 * Parc de comptes : combien de comptes du personnel existent, combien se
 * sont connectes, combien dorment.
 *
 * last_login_at ne garde que la derniere connexion : il dit « s'est connecte
 * au moins une fois depuis telle date », jamais combien de fois. Le rythme
 * reel se lit dans ActivityTimeline.
 */
class AccountCoverage
{
    public function __construct(
        private readonly UsageWindow $window,
        private readonly UsageActorDirectory $actors,
    ) {
    }

    /**
     * @param int[] $activeStaffIds comptes du personnel ayant ecrit dans la periode
     */
    public function build(array $activeStaffIds): array
    {
        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->get(['id', 'is_active', 'last_login_at', 'last_seen_at', 'created_at']);

        $byRole = [];
        $students = ['comptes' => 0, 'vus_dans_la_periode' => 0];
        $active = array_flip($activeStaffIds);

        foreach ($users as $user) {
            $bucket = $this->actors->bucket((int) $user->id);
            $seen = $this->inWindow($user->last_seen_at) || $this->inWindow($user->last_login_at);

            if ($bucket === UsageActorDirectory::ETUDIANTS) {
                $students['comptes']++;
                $students['vus_dans_la_periode'] += $seen ? 1 : 0;
                continue;
            }
            if ($bucket !== UsageActorDirectory::ECOLE) {
                continue;
            }

            $role = $this->actors->role((int) $user->id);
            $byRole[$role] ??= ['role' => $role, 'comptes' => 0, 'desactives' => 0, 'jamais_connectes' => 0, 'vus_dans_la_periode' => 0, 'actifs_dans_la_periode' => 0];
            $byRole[$role]['comptes']++;
            $byRole[$role]['desactives'] += $user->is_active ? 0 : 1;
            $byRole[$role]['jamais_connectes'] += $user->last_login_at === null ? 1 : 0;
            $byRole[$role]['vus_dans_la_periode'] += $seen ? 1 : 0;
            $byRole[$role]['actifs_dans_la_periode'] += isset($active[(int) $user->id]) ? 1 : 0;
        }

        usort($byRole, fn ($a, $b) => $b['comptes'] <=> $a['comptes']);

        return [
            'personnel_par_role' => array_values($byRole),
            'etudiants' => $students,
        ];
    }

    private function inWindow(?string $timestamp): bool
    {
        if ($timestamp === null) {
            return false;
        }
        $at = \Carbon\CarbonImmutable::parse($timestamp);

        return $at->betweenIncluded($this->window->from, $this->window->to);
    }
}
