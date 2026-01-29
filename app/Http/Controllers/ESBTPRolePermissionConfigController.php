<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ESBTPRolePermissionConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:serviceTechnique']);
    }

    public function index(Request $request)
    {
        // Toujours vider le cache pour garantir des données fraîches
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $allowedRoles = [
            'superAdmin',
            'secretaire',
            'coordinateur',
            'etudiant',
            'enseignant',
        ];
        $roles = Role::with('permissions')
            ->whereIn('name', $allowedRoles)
            ->orderByRaw("FIELD(name, 'superAdmin', 'secretaire', 'coordinateur', 'enseignant', 'etudiant')")
            ->get();
        $permissions = Permission::orderBy('name')->get();
        $groupedPermissions = $permissions->groupBy(function ($permission) {
            $name = strtolower($permission->name);

            if (str_contains($name, '.')) {
                $segments = explode('.', $name);
                return $segments[0] ?: 'autres';
            }

            $actionPrefixes = [
                'view',
                'create',
                'edit',
                'delete',
                'restore',
                'force',
                'export',
                'import',
                'manage',
                'access',
                'assign',
                'approve',
                'reject',
                'validate',
                'generate',
                'send',
                'receive',
                'pay',
                'print',
                'sync',
            ];

            $tokens = preg_split('/[\s_]+/', $name);
            if (!$tokens || count($tokens) === 0) {
                return 'autres';
            }

            if (in_array($tokens[0], $actionPrefixes, true)) {
                array_shift($tokens);
                if (isset($tokens[0]) && $tokens[0] === 'own') {
                    array_shift($tokens);
                }
            }

            $groupKey = trim(implode('_', $tokens));

            return $groupKey !== '' ? $groupKey : 'autres';
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

    /**
     * Debug log helper - écrit directement dans un fichier dédié
     * (les logs INFO sont filtrés en production)
     */
    private function debugLog(string $message): void
    {
        file_put_contents(
            storage_path('logs/permissions-debug.log'),
            '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n",
            FILE_APPEND
        );
    }

    public function update(Request $request)
    {
        $this->debugLog('=== UPDATE CALLED ===');
        $this->debugLog('Role: ' . $request->input('role'));
        $this->debugLog('Permissions count: ' . count($request->input('permissions', [])));

        // 1. Vider le cache Spatie AVANT tout (comme dans fix_permissions.php ligne 37)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->debugLog('Cache Spatie vidé (avant)');

        // 2. Validation
        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);
        $this->debugLog('Validation passée');

        $roleName = $validated['role'];
        $permissionNames = $validated['permissions'] ?? [];

        // 3. Transaction explicite pour garantir la persistance
        \DB::beginTransaction();

        try {
            // Trouver le rôle (comme dans fix_permissions.php)
            $role = Role::findByName($roleName);
            $countBefore = \DB::table('role_has_permissions')->where('role_id', $role->id)->count();
            $this->debugLog("Role trouvé: {$role->name} (id={$role->id}), guard={$role->guard_name}");
            $this->debugLog("Permissions AVANT en DB: {$countBefore}");

            // 4. syncPermissions (exactement comme fix_permissions.php ligne 325)
            $role->syncPermissions($permissionNames);
            $this->debugLog('syncPermissions() exécuté');

            // 5. Commit explicite
            \DB::commit();
            $this->debugLog('DB COMMIT effectué');

            // 6. Vider le cache APRÈS le commit (comme fix_permissions.php ligne 413)
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            $this->debugLog('Cache Spatie vidé (après)');

            // 7. Vérification directe en DB (sans cache Eloquent)
            $countAfter = \DB::table('role_has_permissions')->where('role_id', $role->id)->count();
            $this->debugLog("Permissions APRÈS en DB: {$countAfter}");
            $this->debugLog("Demandées: " . count($permissionNames) . " | Avant: {$countBefore} | Après: {$countAfter}");
            $this->debugLog('=== UPDATE SUCCESS ===');

            return redirect()
                ->route('esbtp.roles-permissions.index', ['role' => $roleName])
                ->with('success', "Permissions mises à jour pour {$roleName}: {$countAfter} permissions (avant: {$countBefore}).");

        } catch (\Exception $e) {
            \DB::rollBack();
            $this->debugLog('❌ ERREUR: ' . $e->getMessage());
            $this->debugLog('Stack: ' . $e->getTraceAsString());
            $this->debugLog('=== UPDATE FAILED ===');

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }
}
