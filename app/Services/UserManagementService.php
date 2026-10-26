<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * La gestion d'un compte dépend uniquement des permissions EFFECTIVES.
 * La présence d'un rôle ne donne jamais le droit d'administrer autrui.
 */
class UserManagementService
{
    public function __construct(private readonly PermissionRegistry $registry)
    {
    }

    public function canManage(User $actor, User $target): bool
    {
        if ((int) $actor->id === (int) $target->id) {
            return false;
        }

        if (! $actor->can('personnel.manage') && ! $actor->can('users.manage')) {
            return false;
        }

        // Les titulaires d'un rôle sensible exigent le workflow spécifique
        // de modification avec validation distincte, même pour le support.
        $roles = $target->getRoleNames()->map(
            fn (string $role) => $this->registry->canonicalizeRole($role)
        );
        if ($roles->intersect(['superAdmin', 'serviceTechnique'])->isNotEmpty()) {
            return false;
        }

        if ($actor->can('personnel.roles.manage_all')) {
            return true;
        }

        // Ne pas permettre une prise de contrôle d'un compte disposant de
        // privilèges que l'opérateur ne possède pas lui-même.
        foreach ($target->getAllPermissions() as $permission) {
            if (! $actor->can($permission->name)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    public function manageableRolesFor(User $actor): array
    {
        if (! $actor->can('personnel.roles.assign')) {
            return [];
        }

        return Role::query()->with('permissions')->where('guard_name', 'web')->get()
            ->filter(fn (Role $role) => $this->canAssignRoleModel($actor, $role))
            ->pluck('name')->values()->all();
    }

    public function canAssignRole(User $actor, string $roleName): bool
    {
        if (! $actor->can('personnel.roles.assign')) {
            return false;
        }

        $canonical = $this->registry->canonicalizeRole($roleName);
        if (in_array($canonical, ['superAdmin', 'serviceTechnique'], true)) {
            return false;
        }

        $role = Role::query()->with('permissions')->where('name', $roleName)
            ->where('guard_name', 'web')->first();

        return $role !== null && $this->canAssignRoleModel($actor, $role);
    }

    private function canAssignRoleModel(User $actor, Role $role): bool
    {
        if (in_array($this->registry->canonicalizeRole($role->name), ['superAdmin', 'serviceTechnique'], true)) {
            return false;
        }

        foreach ($role->permissions as $permission) {
            if (! $actor->can($permission->name)) {
                return false;
            }
        }

        return true;
    }
}
