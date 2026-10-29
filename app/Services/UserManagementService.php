<?php

namespace App\Services;

use App\Models\User;

/**
 * Matrice "qui peut gérer qui" pour les utilisateurs.
 *
 * Source : config/permissions.php (clé role_management) lue via
 * PermissionRegistry::manageableRoles().
 */
class UserManagementService
{
    public function __construct(private readonly PermissionRegistry $registry)
    {
    }

    /**
     * $actor peut-il gérer (créer/modifier/supprimer) $target ?
     */
    public function canManage(User $actor, User $target): bool
    {
        // Un user ne peut pas se gérer lui-même via cette policy (utiliser le profil)
        if ($actor->id === $target->id) {
            return false;
        }

        $manageableRoles = $this->manageableRolesFor($actor);
        if (empty($manageableRoles)) {
            return false;
        }

        $targetRoles = $target->roles
            ->pluck('name')
            ->map(fn (string $role) => $this->registry->canonicalizeRole($role))
            ->unique()
            ->values()
            ->all();

        if (empty($targetRoles)) {
            // User sans rôle → seul superAdmin/serviceTechnique peuvent toucher
            $actorRoles = $actor->roles
                ->pluck('name')
                ->map(fn (string $role) => $this->registry->canonicalizeRole($role))
                ->all();

            return count(array_intersect($actorRoles, ['superAdmin', 'serviceTechnique'])) > 0;
        }

        // Chaque rôle de la cible doit être gérable. Un rôle secondaire ne doit
        // jamais permettre de contourner la protection d'un rôle privilégié.
        // Les noms legacy (ex. teacher) sont d'abord ramenés vers le rôle
        // canonique (enseignant), afin que les anciens comptes restent gérables.
        return empty(array_diff($targetRoles, $manageableRoles));
    }

    /**
     * Liste consolidée des rôles que l'acteur peut gérer (union de tous ses rôles).
     */
    public function manageableRolesFor(User $actor): array
    {
        $manageable = [];
        foreach ($actor->roles->pluck('name') as $role) {
            $manageable = array_merge(
                $manageable,
                $this->registry->manageableRoles($this->registry->canonicalizeRole($role)),
            );
        }
        return array_values(array_unique($manageable));
    }

    /**
     * $actor peut-il assigner le rôle $roleName à un user ?
     */
    public function canAssignRole(User $actor, string $roleName): bool
    {
        $roleName = $this->registry->canonicalizeRole($roleName);

        return in_array($roleName, $this->manageableRolesFor($actor), true);
    }
}
