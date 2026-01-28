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
        $allowedRoles = [
            'superAdmin',
            'secretaire',
            'coordinateur',
            'etudiant',
            'enseignant',
        ];
        $roles = Role::whereIn('name', $allowedRoles)
            ->orderByRaw("FIELD(name, 'superAdmin', 'secretaire', 'coordinateur', 'enseignant', 'etudiant')")
            ->get();
        $permissions = Permission::orderBy('name')->get();
        $groupedPermissions = $permissions->groupBy(function ($permission) {
            $segments = preg_split('/[\.\s]/', $permission->name, 2);

            return $segments[0] ?? 'autres';
        })->sortKeys();
        $rolePermissions = $roles->mapWithKeys(function ($role) {
            return [$role->name => $role->permissions->pluck('name')->values()];
        });

        $selectedRoleName = $request->input('role', $roles->first()?->name);
        if ($selectedRoleName && ! $roles->contains('name', $selectedRoleName)) {
            $selectedRoleName = $roles->first()?->name;
        }

        $roleGroups = collect([
            'Administration' => ['superAdmin', 'secretaire'],
            'Pédagogie' => ['coordinateur', 'enseignant'],
            'Étudiants' => ['etudiant'],
        ]);

        $groupedRoles = collect();
        foreach ($roleGroups as $label => $roleNames) {
            $matchingRoles = $roles->filter(function ($role) use ($roleNames) {
                return in_array($role->name, $roleNames, true);
            })->values();

            if ($matchingRoles->isNotEmpty()) {
                $groupedRoles[$label] = $matchingRoles;
            }
        }

        return view('esbtp.roles-permissions.index', compact(
            'roles',
            'permissions',
            'groupedPermissions',
            'groupedRoles',
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
