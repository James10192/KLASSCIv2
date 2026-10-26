<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\User;
use App\Services\PermissionRegistry;
use App\Services\PermissionSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * CLI Permission Supervision API.
 *
 * Read-only endpoints to inspect roles & permissions of a tenant from
 * klassci-cli. Used to audit which permissions are assigned to which role,
 * cross-tenant, without UI access.
 */
class CLIPermissionController extends BaseApiController
{
    public function __construct(private readonly PermissionRegistry $registry)
    {
        parent::__construct();
    }

    /**
     * GET /api/cli/permissions
     *
     * List all canonical permissions from the registry, grouped.
     * Optional ?group=Académique filters by group.
     */
    public function permissions(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $filterGroup = $request->query('group');

        $registryPerms = $this->registry->all();
        $dbPermNames = Permission::query()->pluck('name')->all();
        $dbSet = array_flip($dbPermNames);

        $items = $registryPerms
            ->map(function ($meta, $name) use ($dbSet) {
                return [
                    'name' => $name,
                    'label' => $meta['label'] ?? $name,
                    'group' => $meta['group'] ?? 'Autres',
                    'icon' => $meta['icon'] ?? null,
                    'aliases' => $meta['aliases'] ?? [],
                    'in_db' => isset($dbSet[$name]),
                ];
            })
            ->values();

        if ($filterGroup) {
            $items = $items->filter(fn ($p) => $p['group'] === $filterGroup)->values();
        }

        return $this->successResponse([
            'total_canonical' => $items->count(),
            'in_db_count' => $items->where('in_db', true)->count(),
            'missing_in_db_count' => $items->where('in_db', false)->count(),
            'permissions' => $items,
        ], 'Canonical permissions from registry');
    }

    /**
     * GET /api/cli/permissions/audit
     *
     * Run permissions:audit and return JSON. Surfaces broken / hors-registry /
     * orphan permissions for the current tenant.
     */
    public function audit(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        try {
            $exitCode = Artisan::call('permissions:audit', ['--json' => true]);
            $jsonPath = storage_path('app/permissions-audit.json');

            $payload = file_exists($jsonPath)
                ? json_decode((string) file_get_contents($jsonPath), true)
                : null;

            return $this->successResponse([
                'exit_code' => $exitCode,
                'audit' => $payload,
            ], 'Audit completed');
        } catch (\Throwable $e) {
            Log::error('CLI: permissions audit failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Audit failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/cli/roles
     *
     * List all roles with their permission count and metadata.
     */
    public function roles(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $roles = Role::query()
            ->withCount('permissions')
            ->orderBy('name')
            ->get();
        $userCounts = $this->userCountsByRole($roles->pluck('id')->all());

        $items = $roles->map(function (Role $role) use ($userCounts) {
            $meta = $this->registry->roleMeta($role->name);
            return [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'label' => $meta['label'] ?? $role->name,
                'is_custom' => (bool) ($role->is_custom ?? false),
                'users_count' => $userCounts[$role->id] ?? 0,
                'permissions_count' => $role->permissions_count,
                'group' => $meta['group'] ?? null,
            ];
        });

        return $this->successResponse([
            'total' => $items->count(),
            'roles' => $items,
        ], 'Roles list');
    }

    public function roleStore(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'],
            'label_fr' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        if (! $request->user()->checkPermissionTo('personnel.roles.create', 'web')) {
            return $this->errorResponse('Permission de création de rôle absente.', [], 403);
        }
        foreach ($validated['permissions'] ?? [] as $permission) {
            if (Permission::query()->where('name', $permission)->where('guard_name', 'web')->exists()
                && ! $request->user()->checkPermissionTo($permission, 'web')) {
                return $this->errorResponse('Permission non délégable : '.$permission, [], 403);
            }
        }

        $reserved = array_keys(config('permissions.roles', []));
        if (in_array($validated['name'], $reserved, true)) {
            return $this->errorResponse('Ce nom est réservé à un rôle système.', [], 422);
        }

        // Validation hors mutation, puis rôle + droits + audit dans une transaction.
        // En cas d'échec, pas de rôle créé à moitié ni de permissions non tracées.
        try {
            [$role, $granted, $missing] = DB::transaction(function () use ($validated, $request) {
                $existing = Role::query()->where('name', $validated['name'])
                    ->where('guard_name', 'web')->first();
                if ($existing) {
                    return [null, [], []];
                }

                // Jamais convertir un rôle d'un autre guard : les homonymes
                // web/api peuvent légitimement coexister.
                $role = new Role();
                $role->name = $validated['name'];
                $role->guard_name = 'web';
                $role->label_fr = $validated['label_fr'];
                $role->description = $validated['description'] ?? null;
                $role->is_custom = true;
                $role->created_by_user_id = $request->user()->id;
                $role->save();

                $granted = [];
                $missing = [];
                foreach (array_unique($validated['permissions'] ?? []) as $perm) {
                    $permModel = Permission::where('name', $perm)->where('guard_name', 'web')->first();
                    if (! $permModel) {
                        $missing[] = $perm;
                        continue;
                    }
                    $role->givePermissionTo($permModel);
                    $granted[] = $perm;
                }

                app(\App\Listeners\AuditPermissionChange::class)->enregistrerDecisionDeRole(
                    $role,
                    ['permissions' => []],
                    ['permissions' => $granted],
                    $request->user()->id,
                    'Création du rôle personnalisé depuis la CLI'
                );

                return [$role, $granted, $missing];
            });
        } catch (\Throwable $e) {
            Log::error('CLI: creation role annulee (transaction/audit)', [
                'type' => get_class($e),
                'role' => $validated['name'],
            ]);
            return $this->errorResponse('Création impossible : aucune modification enregistrée.', [], 500);
        }

        if ($role === null) {
            return $this->errorResponse("Role '{$validated['name']}' already exists", [], 422);
        }

        app()['cache']->forget(config('permission.cache.key', 'spatie.permission.cache'));

        return $this->successResponse([
            'role' => $role->name,
            'label_fr' => $role->label_fr,
            'is_custom' => true,
            'granted' => $granted,
            'missing_in_db' => $missing,
        ], "Rôle custom '{$role->name}' créé");
    }

    /**
     * Retire des permissions a un role, sur CETTE instance seulement.
     *
     * Le pendant de roleGrant, qui n'existait pas : on savait etendre un role,
     * pas revenir en arriere. Un profil pose trop large restait trop large.
     *
     * Les extensions locales sont reinscrites apres l'operation : ce qui a ete
     * retire cesse d'etre protege de la synchronisation, sinon le nettoyage le
     * remettrait indefiniment au rang de « decision voulue ».
     */
    public function roleRevoke(Request $request, string $role): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $perms = $request->input('permissions', []);
        if (!is_array($perms) || empty($perms)) {
            return $this->errorResponse('permissions[] requis', [], 422);
        }

        $roleModel = Role::where('name', $role)->where('guard_name', 'web')->first();
        if (!$roleModel) {
            return $this->errorResponse("Role '{$role}' not found", [], 404);
        }

        // On ne retire jamais a superAdmin ni a serviceTechnique : ce sont les
        // roles qui permettent de reparer une instance. Se couper cette branche
        // a distance laisserait l'ecole sans recours.
        if (in_array($role, ['superAdmin', 'serviceTechnique'], true)) {
            return $this->errorResponse("Le role '{$role}' ne se retire pas depuis le CLI.", [], 422);
        }

        if (! $request->user()->checkPermissionTo('personnel.manage', 'web')) {
            return $this->errorResponse('Permission personnel.manage absente.', [], 403);
        }
        // Le token d'administration ne donne aucun droit Spatie de plus.
        foreach ($perms as $permission) {
            if (! is_string($permission) || ! $request->user()->checkPermissionTo($permission, 'web')) {
                return $this->errorResponse('Permission hors du périmètre de l opérateur.', [], 403);
            }
        }

        $extensions = app(\App\Services\ExtensionsDeRole::class);

        // Changement de droits et trace durable dans la MEME transaction.
        // Une migration manquante ou un audit en erreur ne doit jamais laisser
        // un role modifie a moitie.
        $revoquees = DB::transaction(function () use ($roleModel, $perms, $extensions, $role, $request) {
            app(\App\Services\Personnel\RolePermissionMutationGuard::class)->lockAndAssertSafe($roleModel);
            $existantes = $roleModel->permissions()->pluck('name')->all();
            $revoquees = [];
            foreach ($perms as $perm) {
                if (in_array($perm, $existantes, true)) {
                    $roleModel->revokePermissionTo($perm);
                    $revoquees[] = $perm;
                }
            }

            $apres = $roleModel->fresh()->permissions()->pluck('name')->all();
            app(\App\Listeners\AuditPermissionChange::class)->enregistrerDecisionDeRole(
                $roleModel, ['permissions' => $existantes], ['permissions' => $apres],
                $request->user()->id, $request->input('motif') ?: 'Révocation CLI'
            );
            $extensions->enregistrer(
                $role, $apres, $request->input('motif'), $request->user()->id
            );

            return $revoquees;
        });

        app()['cache']->forget(config('permission.cache.key', 'spatie.permission.cache'));

        return $this->successResponse([
            'role' => $role,
            'revoquees' => $revoquees,
            'absentes' => array_values(array_diff($perms, $revoquees)),
            'restantes' => $roleModel->fresh()->permissions()->count(),
            'extensions_locales' => $extensions->pour($role),
        ], "Permissions retirees du role '{$role}'");
    }

    public function roleGrant(Request $request, string $role): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        // Le grant suit la même interdiction que roleRevoke.
        if (in_array($role, ['superAdmin', 'serviceTechnique'], true)) {
            return $this->errorResponse("Le role '{$role}' n'est pas modifiable depuis le CLI.", [], 422);
        }

        $perms = $request->input('permissions', []);
        if (!is_array($perms) || empty($perms)) {
            return $this->errorResponse('permissions[] requis', [], 422);
        }

        $roleModel = Role::where('name', $role)->where('guard_name', 'web')->first();
        if (!$roleModel) {
            return $this->errorResponse("Role '{$role}' not found", [], 404);
        }

        if (! $request->user()->checkPermissionTo('personnel.manage', 'web')) {
            return $this->errorResponse('Permission personnel.manage absente.', [], 403);
        }
        // Le token d'administration ne donne aucun droit Spatie de plus.
        foreach ($perms as $permission) {
            if (! is_string($permission) || ! $request->user()->checkPermissionTo($permission, 'web')) {
                return $this->errorResponse('Permission hors du périmètre de l opérateur.', [], 403);
            }
        }

        $extensions = app(\App\Services\ExtensionsDeRole::class);
        [$granted, $missing] = DB::transaction(function () use ($roleModel, $perms, $extensions, $role, $request) {
            app(\App\Services\Personnel\RolePermissionMutationGuard::class)->lockAndAssertSafe($roleModel);
            $avant = $roleModel->permissions()->pluck('name')->all();
            $granted = [];
            $missing = [];
            foreach ($perms as $perm) {
                $permModel = Permission::where('name', $perm)->where('guard_name', 'web')->first();
                if (! $permModel) {
                    $missing[] = $perm;
                    continue;
                }
                if (! $roleModel->hasPermissionTo($perm)) {
                    $roleModel->givePermissionTo($permModel);
                    $granted[] = $perm;
                }
            }

            $apres = $roleModel->fresh()->permissions()->pluck('name')->all();
            app(\App\Listeners\AuditPermissionChange::class)->enregistrerDecisionDeRole(
                $roleModel, ['permissions' => $avant], ['permissions' => $apres],
                $request->user()->id, $request->input('motif') ?: 'Attribution CLI'
            );
            $extensions->enregistrer(
                $role, $apres, $request->input('motif'), $request->user()->id
            );

            return [$granted, $missing];
        });

        app()['cache']->forget(config('permission.cache.key', 'spatie.permission.cache'));

        return $this->successResponse([
            'role' => $role,
            'granted' => $granted,
            'already_had' => array_values(array_diff($perms, $granted, $missing)),
            'missing_in_db' => $missing,
            // Ce qui, sur ce role, ne viendra jamais des defauts partages et ne
            // survivra que parce qu'il est inscrit ici.
            'extensions_locales' => $extensions->pour($role),
        ], "Permissions accordées au rôle '{$role}'");
    }

    public function roleShow(Request $request, string $role): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $guard = $request->query('guard');
        $roleQuery = Role::where('name', $role)
            ->with('permissions:id,name')
            ->when($guard, fn ($query) => $query->where('guard_name', $guard));
        $matchingRoles = $roleQuery->get();

        if (!$guard && $matchingRoles->count() > 1) {
            return $this->errorResponse(
                "Several roles named '{$role}' exist; specify the guard query parameter",
                ['guards' => $matchingRoles->pluck('guard_name')->values()->all()],
                409
            );
        }

        $roleModel = $matchingRoles->first();
        if (!$roleModel) {
            return $this->errorResponse("Role '{$role}' not found", [], 404);
        }

        $assigned = $roleModel->permissions->pluck('name')->all();
        $canonicalDefaults = $this->registry->defaultPermissionsFor($role);

        $assignedCanonical = array_values(array_unique(array_map(
            fn ($p) => $this->registry->canonicalize($p),
            $assigned
        )));

        $missingCanonical = array_values(array_diff($canonicalDefaults, $assignedCanonical));
        $extraVsDefaults = array_values(array_diff($assignedCanonical, $canonicalDefaults));

        $registryPerms = $this->registry->all();
        $assignedDetails = collect($assigned)
            ->map(function ($name) use ($registryPerms) {
                $canonical = $this->registry->canonicalize($name);
                $meta = $registryPerms[$canonical] ?? null;
                return [
                    'name' => $name,
                    'canonical' => $canonical,
                    'is_alias' => $canonical !== $name,
                    'label' => $meta['label'] ?? $name,
                    'group' => $meta['group'] ?? 'Autres',
                ];
            })
            ->sortBy(['group', 'label'])
            ->values();

        return $this->successResponse([
            'role' => [
                'name' => $roleModel->name,
                'guard_name' => $roleModel->guard_name,
                'label' => $this->registry->roleMeta($role)['label'] ?? $role,
                'is_custom' => (bool) ($roleModel->is_custom ?? false),
                'users_count' => $this->userCountsByRole([$roleModel->id])[$roleModel->id] ?? 0,
            ],
            'assigned_count' => count($assigned),
            'permissions' => $assignedDetails,
            'diff_vs_canonical_defaults' => [
                'missing' => $missingCanonical,
                'extra' => $extraVsDefaults,
            ],
        ], "Role {$role} details");
    }

    /**
     * Count assigned users without relying on Spatie's Role::users() relation.
     * Roles using an API guard without an auth provider must remain auditable.
     *
     * @param array<int, int|string> $roleIds
     * @return array<int|string, int>
     */
    private function userCountsByRole(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        $configuredModel = config('auth.providers.users.model');
        $userModel = is_string($configuredModel) && class_exists($configuredModel)
            ? $configuredModel
            : User::class;
        $model = new $userModel();
        $pivotTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key', 'model_id');
        $rolePivotKey = PermissionRegistrar::$pivotRole ?: 'role_id';
        $qualifiedModelKey = $model->qualifyColumn($model->getKeyName());

        return $model->newQuery()
            ->join($pivotTable, "{$pivotTable}.{$morphKey}", '=', $qualifiedModelKey)
            ->where("{$pivotTable}.model_type", $model->getMorphClass())
            ->whereIn("{$pivotTable}.{$rolePivotKey}", $roleIds)
            ->selectRaw(
                "{$pivotTable}.{$rolePivotKey} AS role_id, "
                . "COUNT(DISTINCT {$qualifiedModelKey}) AS aggregate"
            )
            ->groupBy("{$pivotTable}.{$rolePivotKey}")
            ->pluck('aggregate', 'role_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * POST /api/cli/permissions/sync
     *
     * Registry-driven permission sync. Délègue à PermissionSyncService —
     * shared with bin/deploy/fix_permissions.php pour éviter le drift.
     */
    public function sync(Request $request, PermissionSyncService $syncService): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        // Une ability Sanctum n'est jamais une habilitation à reconfigurer
        // les permissions de TOUS les comptes d'un établissement. Même
        // garde pour /permissions/sync et son alias legacy /permissions/fix.
        if (! $request->user()->checkPermissionTo('system.technical_access', 'web')
            && ! $request->user()->checkPermissionTo('personnel.roles.manage_all', 'web')) {
            return $this->errorResponse('Maintenance des permissions non autorisée.', [], 403);
        }

        try {
            $result = $syncService->run();

            return $this->successResponse([
                'permissions_synced' => $result['permissions_count'],
                'roles_synced' => $result['roles_count'],
                'roles_with_defaults_assigned' => $result['roles_with_defaults_assigned'],
                'roles_preserved' => $result['roles_preserved'],
                'aliases_healed' => $result['aliases_healed'],
            ], 'Permissions and roles synced from registry');
        } catch (\Throwable $e) {
            Log::error('CLI: permissions sync failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Sync failed: ' . $e->getMessage(), [], 500);
        }
    }
}
