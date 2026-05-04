<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Synchronise rôles & permissions depuis le registry (config/permissions.php).
 *
 * Source de vérité unique pour les deux entrypoints :
 * - bin/deploy/fix_permissions.php (script CLI deploy)
 * - App\Http\Controllers\API\CLI\CLIPermissionController::sync (API klassci-cli)
 *
 * Comportement :
 * - Crée toutes les permissions canoniques + leurs aliases (rétrocompat)
 * - Crée tous les rôles canoniques
 * - Synchronise les permissions par défaut UNIQUEMENT pour les rôles vides
 *   (préserve les configurations live des tenants en prod)
 * - Healing : pour chaque rôle existant, ajoute les canoniques manquantes
 *   correspondant à ses aliases legacy (migration douce)
 */
class PermissionSyncService
{
    public function __construct(private readonly PermissionRegistry $registry)
    {
    }

    /**
     * Lance la synchronisation. Retourne un payload structuré décrivant les
     * changements appliqués (utile pour la réponse JSON CLI / le log script).
     *
     * @return array{
     *   permissions_count: int,
     *   roles_count: int,
     *   roles_with_defaults_assigned: array<int, array{role: string, permissions_count: int}>,
     *   roles_preserved: array<int, string>,
     *   aliases_healed: array<int, array{role: string, canonicals_added: int}>,
     *   dependencies_healed: array<int, array{role: string, permissions_added: int}>
     * }
     */
    public function run(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allNames = $this->registry->allNames();
        foreach ($allNames as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $roles = $this->registry->roles();
        $roleModels = [];
        foreach ($roles as $name => $meta) {
            $roleModels[$name] = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $assignedRoles = [];
        $preservedRoles = [];
        foreach ($roles->keys() as $roleName) {
            $role = $roleModels[$roleName];
            if ($role->permissions()->count() > 0) {
                $preservedRoles[] = $roleName;
                continue;
            }

            $expanded = $this->expandWithAliases(
                $this->applyPermissionDependencies($this->registry->defaultPermissionsFor($roleName))
            );
            $role->syncPermissions($expanded);
            $assignedRoles[] = ['role' => $roleName, 'permissions_count' => count($expanded)];
        }

        $healed = [];
        foreach ($roles->keys() as $roleName) {
            $role = $roleModels[$roleName];
            $existingNames = $role->permissions->pluck('name')->all();
            $toAdd = [];
            foreach ($existingNames as $name) {
                $canonical = $this->registry->canonicalize($name);
                if ($canonical !== $name && !in_array($canonical, $existingNames, true)) {
                    $toAdd[] = $canonical;
                }
            }
            if (!empty($toAdd)) {
                $role->givePermissionTo($toAdd);
                $healed[] = ['role' => $roleName, 'canonicals_added' => count($toAdd)];
            }
        }

        $dependenciesHealed = [];
        foreach ($roles->keys() as $roleName) {
            $role = $roleModels[$roleName];
            $existingNames = $role->permissions()->pluck('name')->all();
            $withDependencies = $this->expandWithAliases($this->applyPermissionDependencies($existingNames));
            $toAdd = array_values(array_diff($withDependencies, $existingNames));
            if (! empty($toAdd)) {
                $role->givePermissionTo($toAdd);
                $dependenciesHealed[] = ['role' => $roleName, 'permissions_added' => count($toAdd)];
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->registry->clearCache();

        return [
            'permissions_count' => $allNames->count(),
            'roles_count' => $roles->count(),
            'roles_with_defaults_assigned' => $assignedRoles,
            'roles_preserved' => $preservedRoles,
            'aliases_healed' => $healed,
            'dependencies_healed' => $dependenciesHealed,
        ];
    }

    /**
     * Étend une liste de permissions canoniques avec leurs aliases legacy
     * (Lot 6 rétrocompat). @can('view_students') doit continuer de marcher
     * tant qu'on n'a pas migré tout le code vers les canoniques.
     *
     * @param  array<int, string>  $canonicals
     * @return array<int, string>
     */
    private function expandWithAliases(array $canonicals): array
    {
        $expanded = [];
        foreach ($canonicals as $canonical) {
            $expanded[] = $canonical;
            foreach ($this->registry->aliasesOf($canonical) as $alias) {
                $expanded[] = $alias;
            }
        }
        return array_values(array_unique($expanded));
    }

    /**
     * Maintient les permissions dependantes dans les roles synchronises.
     *
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    private function applyPermissionDependencies(array $permissions): array
    {
        if (in_array('personnel.manage', $permissions, true) && ! in_array('personnel.view', $permissions, true)) {
            $permissions[] = 'personnel.view';
        }

        return array_values(array_unique($permissions));
    }
}
