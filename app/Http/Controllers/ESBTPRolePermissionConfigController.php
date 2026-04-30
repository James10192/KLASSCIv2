<?php

namespace App\Http\Controllers;

use App\Services\PermissionRegistry;
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

    public function index(Request $request, PermissionRegistry $registry)
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $allowedRoles = $registry->rolesVisibleInUi()->keys()->all();

        $roles = Role::with('permissions')
            ->whereIn('name', $allowedRoles)
            ->get()
            ->sortBy(fn ($r) => array_search($r->name, $allowedRoles))
            ->values();

        // Toutes les permissions canoniques (les aliases sont masqués par défaut)
        $showLegacy = $request->boolean('show_legacy', false);
        $registryPerms = $registry->all();

        $permissions = $showLegacy
            ? Permission::orderBy('name')->get()
            : Permission::whereIn('name', $registryPerms->keys()->all())->orderBy('name')->get();

        // Group permissions by registry's group + sort
        $groupOrder = [
            'Tableau de bord', 'Administration', 'Étudiants', 'Inscriptions',
            'Académique', 'Notes & Évaluations', 'Bulletins', 'Présences',
            'Planning', 'Paiements', 'Frais', 'Comptabilité', 'Personnel',
            'Communication', 'Rapports', 'Résultats', 'Identité',
            'Modules', 'Sécurité', 'Système',
        ];

        $groupedPermissions = $permissions->groupBy(function ($permission) use ($registryPerms, $registry) {
            $canonical = $registry->canonicalize($permission->name);
            $meta = $registryPerms[$canonical] ?? null;
            return $meta['group'] ?? 'Autres';
        });

        $sortedGroups = collect();
        foreach ($groupOrder as $groupName) {
            if ($groupedPermissions->has($groupName)) {
                $sortedGroups[$groupName] = $groupedPermissions[$groupName];
            }
        }
        foreach ($groupedPermissions as $groupName => $items) {
            if (! $sortedGroups->has($groupName)) {
                $sortedGroups[$groupName] = $items;
            }
        }

        // Catalogue : permission name → [label_fr, group, icon, is_alias, canonical, deprecated_reason]
        $catalog = [];
        foreach ($permissions as $perm) {
            $canonical = $registry->canonicalize($perm->name);
            $meta = $registryPerms[$canonical] ?? null;
            $isAlias = $perm->name !== $canonical;

            $catalog[$perm->name] = [
                'label' => $meta['label'] ?? $perm->name,
                'group' => $meta['group'] ?? 'Autres',
                'icon' => $meta['icon'] ?? 'fa-key',
                'is_alias' => $isAlias,
                'canonical' => $canonical,
                'deprecated_reason' => $registry->isDeprecated($perm->name) ? $registry->deprecatedReason($perm->name) : null,
            ];
        }

        $rolePermissions = $roles->mapWithKeys(function ($role) {
            return [$role->name => $role->permissions->pluck('name')->values()];
        });

        $selectedRoleName = $request->input('role', $roles->first()?->name);
        if ($selectedRoleName && ! $roles->contains('name', $selectedRoleName)) {
            $selectedRoleName = $roles->first()?->name;
        }

        // Métadonnées des rôles (label FR, icône, description) depuis le registry
        $roleMeta = $registry->roles();
        $roleLabels = $roleMeta->mapWithKeys(fn ($m, $name) => [$name => $m['label'] ?? $name])->all();
        $roleDescriptions = $roleMeta->mapWithKeys(fn ($m, $name) => [$name => $m['description'] ?? ''])->all();
        $roleIcons = $roleMeta->mapWithKeys(fn ($m, $name) => [$name => $m['icon'] ?? 'fa-user'])->all();

        // Groupement des rôles par catégorie (Administration / Pédagogie / etc.)
        $groupedRoles = $roles->groupBy(fn ($r) => $roleMeta[$r->name]['group'] ?? 'Autres');

        // Matrice de gestion users (qui peut gérer qui) — lecture seule pour info
        $managementMatrix = collect($allowedRoles)->mapWithKeys(fn ($role) => [
            $role => $registry->manageableRoles($role),
        ]);

        return view('esbtp.roles-permissions.index', compact(
            'roles', 'permissions', 'groupedPermissions', 'groupedRoles',
            'rolePermissions', 'selectedRoleName', 'catalog', 'sortedGroups',
            'roleLabels', 'roleDescriptions', 'roleIcons',
            'managementMatrix', 'showLegacy'
        ));
    }

    /**
     * Lance permissions:audit et retourne le résultat en JSON pour affichage UI.
     */
    public function audit()
    {
        \Artisan::call('permissions:audit', ['--json' => true]);

        $path = storage_path('app/permissions-audit.json');
        if (! file_exists($path)) {
            return response()->json(['error' => 'Audit non disponible'], 500);
        }

        return response()->json(json_decode(file_get_contents($path), true));
    }

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

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->debugLog('Cache Spatie vidé (avant)');

        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);
        $this->debugLog('Validation passée');

        $roleName = $validated['role'];
        $permissionNames = $validated['permissions'] ?? [];

        \DB::beginTransaction();

        try {
            $role = Role::findByName($roleName);
            $countBefore = \DB::table('role_has_permissions')->where('role_id', $role->id)->count();
            $this->debugLog("Role trouvé: {$role->name} (id={$role->id})");
            $this->debugLog("Permissions AVANT en DB: {$countBefore}");

            $role->syncPermissions($permissionNames);
            $this->debugLog('syncPermissions() exécuté');

            \DB::commit();
            $this->debugLog('DB COMMIT effectué');

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            $this->debugLog('Cache Spatie vidé (après)');

            $countAfter = \DB::table('role_has_permissions')->where('role_id', $role->id)->count();
            $this->debugLog("Permissions APRÈS en DB: {$countAfter}");
            $this->debugLog('=== UPDATE SUCCESS ===');

            return redirect()
                ->route('esbtp.roles-permissions.index', ['role' => $roleName])
                ->with('success', "Permissions mises à jour pour {$roleName}: {$countAfter} permissions (avant: {$countBefore}).");

        } catch (\Exception $e) {
            \DB::rollBack();
            $this->debugLog('❌ ERREUR: ' . $e->getMessage());
            $this->debugLog('=== UPDATE FAILED ===');

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Restaure les permissions par défaut d'un rôle depuis le registry.
     */
    public function restoreDefaults(Request $request, PermissionRegistry $registry)
    {
        $validated = $request->validate(['role' => 'required|exists:roles,name']);
        $roleName = $validated['role'];

        $canonicals = $registry->defaultPermissionsFor($roleName);
        $expanded = [];
        foreach ($canonicals as $canonical) {
            $expanded[] = $canonical;
            foreach ($registry->aliasesOf($canonical) as $alias) {
                $expanded[] = $alias;
            }
        }
        $expanded = array_values(array_unique($expanded));

        // Filtrer pour ne garder que les permissions qui existent en DB
        $existing = Permission::whereIn('name', $expanded)->pluck('name')->all();

        \DB::beginTransaction();
        try {
            $role = Role::findByName($roleName);
            $role->syncPermissions($existing);
            \DB::commit();
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            return redirect()
                ->route('esbtp.roles-permissions.index', ['role' => $roleName])
                ->with('success', 'Permissions par défaut restaurées pour ' . $roleName . ' (' . count($existing) . ' permissions).');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
