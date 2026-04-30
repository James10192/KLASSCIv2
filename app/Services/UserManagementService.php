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

        $targetRoles = $target->roles->pluck('name')->all();
        if (empty($targetRoles)) {
            // User sans rôle → seul superAdmin/serviceTechnique peuvent toucher
            return $actor->hasAnyRole(['superAdmin', 'serviceTechnique']);
        }

        // Au moins un des rôles cibles doit être dans la liste manageable
        return ! empty(array_intersect($targetRoles, $manageableRoles));
    }

    /**
     * Liste consolidée des rôles que l'acteur peut gérer (union de tous ses rôles).
     */
    public function manageableRolesFor(User $actor): array
    {
        $manageable = [];
        foreach ($actor->roles->pluck('name') as $role) {
            $manageable = array_merge($manageable, $this->registry->manageableRoles($role));
        }
        return array_values(array_unique($manageable));
    }

    /**
     * $actor peut-il assigner le rôle $roleName à un user ?
     */
    public function canAssignRole(User $actor, string $roleName): bool
    {
        return in_array($roleName, $this->manageableRolesFor($actor), true);
    }
}
