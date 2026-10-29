<?php

namespace App\Policies;

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy pour la gestion granulaire des utilisateurs.
 *
 * Remplace l'ancien authorize('manage-users') monolithique par un check
 * basé sur la matrice config/permissions.php → role_management.
 *
 * Note : Gate::before pour superAdmin court-circuite tous ces checks
 * (AuthServiceProvider Lot 0) — superAdmin peut tout gérer.
 */
class UserManagementPolicy
{
    use HandlesAuthorization;

    public function __construct(private readonly UserManagementService $service)
    {
    }

    public function view(User $actor, User $target): bool
    {
        return $this->service->canManage($actor, $target);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->service->canManage($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->service->canManage($actor, $target);
    }

    /**
     * Vérifie si l'acteur peut assigner un rôle spécifique au cible.
     * Appel : Gate::allows('assignRole', [$targetUser, 'enseignant'])
     */
    public function assignRole(User $actor, User $target, string $role): bool
    {
        if (! $this->service->canManage($actor, $target)) {
            return false;
        }
        return $this->service->canAssignRole($actor, $role);
    }
}
