<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ESBTPRolePermissionConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:serviceTechnique']);
    }

    public function index(Request $request)
    {
        $roles = Role::orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        $groupedPermissions = $permissions->groupBy(function ($permission) {
            $segments = preg_split('/[\.\s]/', $permission->name, 2);

            return $segments[0] ?? 'autres';
        })->sortKeys();
        $rolePermissions = $roles->mapWithKeys(function ($role) {
            return [$role->name => $role->permissions->pluck('name')->values()];
        });

        $selectedRoleName = $request->input('role', $roles->first()?->name);

        return view('esbtp.roles-permissions.index', compact(
            'roles',
            'permissions',
            'groupedPermissions',
            'rolePermissions',
            'selectedRoleName'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::findByName($validated['role']);
        $permissionNames = $validated['permissions'] ?? [];

        $role->syncPermissions($permissionNames);

        return redirect()
            ->route('esbtp.roles-permissions.index', ['role' => $role->name])
            ->with('success', 'Permissions mises a jour pour le role '.$role->name.'.');
    }
}
