<?php

namespace App\Services\Usage;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Qui est qui : range chaque compte dans un groupe d'acteurs.
 *
 *  - ecole     : le personnel de l'etablissement, seul groupe qui compte
 *                pour juger de l'adoption ;
 *  - etudiants : comptes etudiants et parents ;
 *  - klassci   : nos propres comptes (roles internes, exclusions explicites).
 *
 * Les actions sans compte ou venues de nos outils sont rangees par
 * l'agregateur selon leur origine, pas ici.
 */
class UsageActorDirectory
{
    public const ECOLE = 'ecole';
    public const ETUDIANTS = 'etudiants';
    public const KLASSCI = 'klassci';
    public const SYSTEME = 'systeme';

    /** @var array<int, array{bucket: string, role: string, name: string}> */
    private array $actors = [];

    /**
     * @param int[] $excludedUserIds comptes a traiter comme KLASSCI
     */
    public function __construct(array $excludedUserIds = [])
    {
        $internalRoles = config('usage_report.internal_roles', []);
        $nonStaffRoles = config('usage_report.non_staff_roles', []);

        $roles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', User::class)
            ->get(['model_has_roles.model_id as user_id', 'roles.name as role'])
            ->groupBy('user_id');

        $users = DB::table('users')->get(['id', 'name']); // comptes supprimes compris : leurs actions passees restent les leurs

        foreach ($users as $user) {
            $userRoles = $roles->get($user->id, collect())->pluck('role')->all();
            $this->actors[$user->id] = [
                'bucket' => $this->bucketFor($user->id, $userRoles, $excludedUserIds, $internalRoles, $nonStaffRoles),
                'role' => $this->mainRole($userRoles, $internalRoles),
                'name' => (string) $user->name,
            ];
        }
    }

    public function bucket(?int $userId): string
    {
        return $this->actors[$userId]['bucket'] ?? self::SYSTEME;
    }

    public function role(int $userId): string
    {
        return $this->actors[$userId]['role'] ?? 'inconnu';
    }

    public function name(int $userId): string
    {
        return $this->actors[$userId]['name'] ?? '';
    }

    /** @return int[] */
    public function idsIn(string $bucket): array
    {
        return array_keys(array_filter($this->actors, fn ($a) => $a['bucket'] === $bucket));
    }

    private function bucketFor(int $id, array $roles, array $excluded, array $internal, array $nonStaff): string
    {
        if (in_array($id, $excluded, true) || array_intersect($roles, $internal)) {
            return self::KLASSCI;
        }
        if ($roles !== [] && array_diff($roles, $nonStaff) === []) {
            return self::ETUDIANTS;
        }

        return self::ECOLE;
    }

    /** Un role unique a afficher : le role interne s'il existe, sinon le premier. */
    private function mainRole(array $roles, array $internal): string
    {
        $internalRole = array_values(array_intersect($roles, $internal));

        return $internalRole[0] ?? ($roles[0] ?? 'sans_role');
    }
}
