<?php

namespace App\Http\Controllers;

use App\Services\PermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public function update(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $roleName = $validated['role'];
        $permissionNames = array_values(array_unique($validated['permissions'] ?? []));

        try {
            [$avant, $apres] = $this->appliquer($roleName, $permissionNames, 'Accordé depuis la configuration des rôles.');
        } catch (\Throwable $e) {
            Log::error('Configuration des rôles : enregistrement refusé', ['role' => $roleName, 'erreur' => $e->getMessage()]);

            return $this->repondre($request, false, 'Les permissions n\'ont pas été enregistrées : ' . $e->getMessage(), $roleName, null, 500);
        }

        Log::info('Configuration des rôles : permissions enregistrées', [
            'role' => $roleName, 'avant' => $avant, 'apres' => count($apres), 'par' => auth()->id(),
        ]);

        return $this->repondre($request, true, "Permissions enregistrées : {$roleName} en a désormais " . count($apres) . " (avant : {$avant}).", $roleName, $apres);
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

        try {
            // Revenir aux défauts vide aussi les ajouts inscrits pour ce rôle :
            // sans cela, la synchronisation du prochain déploiement remettrait
            // ce que l'école vient de retirer.
            [, $apres] = $this->appliquer($roleName, $existing, 'Défauts restaurés depuis la configuration des rôles.');
        } catch (\Throwable $e) {
            Log::error('Configuration des rôles : restauration refusée', ['role' => $roleName, 'erreur' => $e->getMessage()]);

            return $this->repondre($request, false, 'Les défauts n\'ont pas été restaurés : ' . $e->getMessage(), $roleName, null, 500);
        }

        return $this->repondre($request, true, "Permissions par défaut restaurées pour {$roleName} (" . count($apres) . ' permissions).', $roleName, $apres);
    }

    /**
     * Pose l'ensemble complet des permissions d'un rôle, et inscrit ce qui sort
     * des défauts pour que la synchronisation des déploiements le respecte.
     *
     * @return array{0:int, 1:list<string>} nombre avant, permissions après
     */
    private function appliquer(string $roleName, array $permissionNames, string $motif): array
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $resultat = DB::transaction(function () use ($roleName, $permissionNames, $motif) {
            $role = Role::findByName($roleName);
            $avant = DB::table('role_has_permissions')->where('role_id', $role->id)->count();

            $role->syncPermissions($permissionNames);
            app(\App\Services\ExtensionsDeRole::class)->enregistrer($role->name, $permissionNames, $motif, auth()->id());

            return [$avant, $role->id];
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $apres = Permission::whereIn('id', DB::table('role_has_permissions')->where('role_id', $resultat[1])->pluck('permission_id'))
            ->orderBy('name')->pluck('name')->values()->all();

        return [$resultat[0], $apres];
    }

    /**
     * JSON pour l'écran (aucun rechargement), redirection pour un envoi classique.
     */
    private function repondre(Request $request, bool $ok, string $message, string $roleName, ?array $permissions, int $statutErreur = 422)
    {
        if ($request->expectsJson()) {
            return response()->json(array_filter([
                'success' => $ok,
                'message' => $message,
                'role' => $roleName,
                'permissions' => $permissions,
            ], fn ($v) => $v !== null), $ok ? 200 : $statutErreur);
        }

        return $ok
            ? redirect()->route('esbtp.roles-permissions.index', ['role' => $roleName])->with('success', $message)
            : redirect()->back()->with('error', $message);
    }
}
