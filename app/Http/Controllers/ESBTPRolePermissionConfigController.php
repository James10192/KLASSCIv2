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
        // DEBUG: Log l'entrée dans index
        \Log::info('🔍 [PERMISSIONS] index() appelé', [
            'role_param' => $request->input('role'),
            'has_success_flash' => session()->has('success'),
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Toujours vider le cache pour garantir des données fraîches
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        \Log::info('🔍 [PERMISSIONS] Cache Spatie vidé');

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

        // DEBUG: Log les permissions par rôle
        \Log::info('🔍 [PERMISSIONS] Permissions chargées depuis DB:', [
            'roles_count' => $roles->count(),
            'permissions_per_role' => $rolePermissions->map(fn($perms) => $perms->count())->toArray(),
        ]);

        // DEBUG: Log détaillé pour le rôle demandé
        $debugRole = $request->input('role', 'coordinateur');
        if (isset($rolePermissions[$debugRole])) {
            \Log::info("🔍 [PERMISSIONS] Détail permissions pour '{$debugRole}':", [
                'count' => $rolePermissions[$debugRole]->count(),
                'permissions' => $rolePermissions[$debugRole]->take(10)->toArray(),
            ]);
        }

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
        // DEBUG CRITIQUE: Log immédiat pour confirmer que la méthode est appelée
        \Log::emergency('🚨🚨🚨 [PERMISSIONS] UPDATE METHOD CALLED - ' . now()->toDateTimeString());
        file_put_contents(storage_path('logs/permissions-debug.log'),
            '🚨 UPDATE CALLED: ' . now()->toDateTimeString() . "\n" .
            'Role: ' . $request->input('role') . "\n" .
            'Permissions count: ' . count($request->input('permissions', [])) . "\n\n",
            FILE_APPEND
        );

        // DEBUG: Log toutes les données reçues
        \Log::info('🔧 [PERMISSIONS UPDATE] Requête reçue', [
            'role' => $request->input('role'),
            'permissions_count' => count($request->input('permissions', [])),
            'permissions_sample' => array_slice($request->input('permissions', []), 0, 5),
            'all_input_keys' => array_keys($request->all()),
            'method' => $request->method(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        \Log::info('🔧 [PERMISSIONS UPDATE] Validation passée', [
            'role' => $validated['role'],
            'permissions_count' => count($validated['permissions'] ?? []),
        ]);

        $role = Role::findByName($validated['role']);
        $permissionNames = $validated['permissions'] ?? [];

        // DEBUG: Log les permissions AVANT modification
        $beforePermissions = $role->permissions->pluck('name')->toArray();
        \Log::info('🔧 [PERMISSIONS UPDATE] AVANT syncPermissions', [
            'role' => $role->name,
            'role_id' => $role->id,
            'permissions_before_count' => count($beforePermissions),
            'permissions_before_sample' => array_slice($beforePermissions, 0, 5),
        ]);

        // DEBUG: Vérifier en DB AVANT syncPermissions
        $dbBefore = \DB::table('role_has_permissions')
            ->where('role_id', $role->id)
            ->pluck('permission_id')
            ->toArray();
        \Log::info('🔧 [PERMISSIONS UPDATE] DB AVANT sync', [
            'role_id' => $role->id,
            'permission_ids_in_db' => $dbBefore,
            'count' => count($dbBefore),
        ]);

        // Exécuter syncPermissions
        \Log::info('🔧 [PERMISSIONS UPDATE] Appel syncPermissions avec:', [
            'permissions_to_sync' => $permissionNames,
            'count' => count($permissionNames),
        ]);

        try {
            $role->syncPermissions($permissionNames);
            \Log::info('🔧 [PERMISSIONS UPDATE] syncPermissions exécuté AVEC SUCCÈS');
            file_put_contents(storage_path('logs/permissions-debug.log'),
                '✅ syncPermissions SUCCESS for role ' . $role->name . "\n", FILE_APPEND);
        } catch (\Exception $e) {
            \Log::error('❌ [PERMISSIONS UPDATE] ERREUR syncPermissions: ' . $e->getMessage());
            file_put_contents(storage_path('logs/permissions-debug.log'),
                '❌ syncPermissions ERROR: ' . $e->getMessage() . "\n", FILE_APPEND);
            throw $e;
        }

        // DEBUG: Vérifier en DB APRÈS syncPermissions (query directe, pas de cache)
        $dbAfter = \DB::table('role_has_permissions')
            ->where('role_id', $role->id)
            ->pluck('permission_id')
            ->toArray();
        \Log::info('🔧 [PERMISSIONS UPDATE] DB APRÈS sync', [
            'role_id' => $role->id,
            'permission_ids_in_db' => $dbAfter,
            'count' => count($dbAfter),
            'added' => array_diff($dbAfter, $dbBefore),
            'removed' => array_diff($dbBefore, $dbAfter),
        ]);

        // Vider TOUS les caches possibles
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        \Cache::flush(); // Vider tout le cache Laravel aussi
        \Log::info('🔧 [PERMISSIONS UPDATE] Cache Spatie + Laravel vidés');

        // DEBUG: Recharger le rôle FRAIS depuis DB (pas de cache Eloquent)
        $freshRole = Role::where('id', $role->id)->with('permissions')->first();
        $afterPermissions = $freshRole->permissions->pluck('name')->toArray();
        \Log::info('🔧 [PERMISSIONS UPDATE] Permissions rechargées (fresh):', [
            'role' => $freshRole->name,
            'permissions_count' => count($afterPermissions),
            'permissions_sample' => array_slice($afterPermissions, 0, 10),
        ]);

        // DEBUG: Vérification finale avec Query Builder (aucun cache possible)
        $dbCheck = \DB::table('role_has_permissions')
            ->where('role_id', $role->id)
            ->count();
        \Log::info('🔧 [PERMISSIONS UPDATE] Vérification finale DB directe', [
            'role_id' => $role->id,
            'permissions_in_db' => $dbCheck,
        ]);

        return redirect()
            ->route('esbtp.roles-permissions.index', ['role' => $role->name])
            ->with('success', 'Permissions mises a jour pour le role '.$role->name.' ('.$dbCheck.' permissions en DB).');
    }
}
