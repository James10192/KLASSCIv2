<?php

namespace App\Policies;

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Accès aux personnes par permission effective, sans matrice de noms de rôles.
 * La lecture est distincte des écritures sur les comptes.
 */
class UserManagementPolicy
{
    use HandlesAuthorization;

    public function __construct(private readonly UserManagementService $service)
    {
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->can('personnel.view')
            || $actor->can('personnel.manage')
            || $actor->can('users.manage');
    }

    public function update(User $actor, User $target): bool
    {
        return $this->service->canManage($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->service->canManage($actor, $target);
    }

    public function assignRole(User $actor, User $target, string $role): bool
    {
        return $this->service->canManage($actor, $target)
            && $this->service->canAssignRole($actor, $role);
    }
}
