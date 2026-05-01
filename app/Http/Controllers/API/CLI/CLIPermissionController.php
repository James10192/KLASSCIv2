<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\PermissionRegistry;
use App\Services\PermissionSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get();

        $items = $roles->map(function (Role $role) {
            $meta = $this->registry->roleMeta($role->name);
            return [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $meta['label'] ?? $role->name,
                'is_custom' => (bool) ($role->is_custom ?? false),
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions_count,
                'group' => $meta['group'] ?? null,
            ];
        });

        return $this->successResponse([
            'total' => $items->count(),
            'roles' => $items,
        ], 'Roles list');
    }

    /**
     * GET /api/cli/roles/{role}
     *
     * Show all permissions assigned to a specific role + diff vs canonical defaults.
     */
    public function roleShow(Request $request, string $role): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $roleModel = Role::where('name', $role)
            ->withCount('users')
            ->with('permissions:id,name')
            ->first();
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
                'label' => $this->registry->roleMeta($role)['label'] ?? $role,
                'is_custom' => (bool) ($roleModel->is_custom ?? false),
                'users_count' => $roleModel->users_count,
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
