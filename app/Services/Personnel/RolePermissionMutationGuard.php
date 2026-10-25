<?php

namespace App\Services\Personnel;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Permission\Models\Role;

/**
 * Verrou commun pour les mutations de permissions de rôles (web historique,
 * CLI et personnel/unified). Appeler DANS une transaction, avant toute écriture.
 * Le nom d'un rôle ne donne jamais une permission à l'opérateur.
 */
final class RolePermissionMutationGuard
{
    public function lockAndAssertSafe(Role $role): void
    {
        // Même ordre que RoleAssignmentService et ESBTPCustomRoleController.
        Role::query()->where('name', 'superAdmin')->where('guard_name', 'web')
            ->lockForUpdate()->first();

        $locked = Role::query()->whereKey($role->id)->where('guard_name', 'web')
            ->lockForUpdate()->firstOrFail();

        if (in_array($locked->name, ['superAdmin', 'serviceTechnique'], true)) {
            throw new AuthorizationException('Rôle de secours : modification interdite.');
        }

        // Modifier un rôle partagé avec un compte protégé en modifierait
        // indirectement les droits, en contournant maker-checker.
        $sensitiveMember = User::query()
            ->whereHas('roles', fn ($query) => $query->where('roles.id', $locked->id))
            ->whereHas('roles', fn ($query) => $query->whereIn('roles.name', ['superAdmin', 'serviceTechnique']))
            ->exists();
        if ($sensitiveMember) {
            throw new AuthorizationException('Rôle attribué à un compte protégé : approbation distincte obligatoire.');
        }
    }
}
