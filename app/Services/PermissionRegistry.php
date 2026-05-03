<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Lecture du registry config/permissions.php.
 *
 * Source unique de vérité pour rôles, permissions, aliases, défauts et
 * matrice de gestion. À utiliser partout au lieu d'accéder directement au config.
 *
 * Lot 8 : roleMeta() lit la DB en priorité (rôles custom + override) avec
 * fallback sur le config pour les rôles système.
 */
class PermissionRegistry
{
    /** Cache des aliases inversés : alias => canonical */
    private ?array $aliasMapCache = null;

    /** Cache des rôles DB (incl. is_custom) pour la requête courante */
    private ?array $dbRolesCache = null;

    public function roles(): Collection
    {
        $configRoles = collect(config('permissions.roles', []));

        // Fusionne les rôles custom de la DB (is_custom = true) qui n'existent pas en config
        $custom = $this->customRolesFromDb();
        if ($custom->isEmpty()) {
            return $configRoles;
        }

        return $configRoles->merge($custom);
    }

    public function rolesVisibleInUi(): Collection
    {
        return $this->roles()->filter(fn ($meta) => ($meta['visible_in_ui'] ?? true) === true);
    }

    public function roleMeta(string $role): ?array
    {
        $configMeta = config('permissions.roles', [])[$role] ?? null;
        $dbRow = $this->dbRoles()[$role] ?? null;

        // Si le rôle n'existe ni en config ni en DB → null
        if ($configMeta === null && $dbRow === null) {
            return null;
        }

        // DB row dispo et a un override (label_fr) ou est custom → DB-first
        if ($dbRow !== null && ((! empty($dbRow['label_fr'])) || ! empty($dbRow['is_custom']))) {
            $configMeta = $configMeta ?? [];

            return array_merge($configMeta, [
                'label' => $dbRow['label_fr'] ?? $configMeta['label'] ?? $role,
                'icon' => $dbRow['icon'] ?? $configMeta['icon'] ?? 'fa-user-tag',
                'description' => $dbRow['description'] ?? $configMeta['description'] ?? '',
                'group' => $configMeta['group'] ?? 'Personnalisé',
                'visible_in_ui' => $configMeta['visible_in_ui'] ?? true,
                'is_custom' => (bool) ($dbRow['is_custom'] ?? false),
            ]);
        }

        // Fallback config (rôle système, pas d'override DB)
        if ($configMeta !== null) {
            return array_merge($configMeta, [
                'is_custom' => false,
            ]);
        }

        return null;
    }

    /**
     * Indique si un rôle est custom (créé depuis l'UI), donc modifiable/supprimable.
     */
    public function roleIsCustom(string $role): bool
    {
        $row = $this->dbRoles()[$role] ?? null;

        return $row !== null && (bool) ($row['is_custom'] ?? false);
    }

    public function all(): Collection
    {
        return collect(config('permissions.permissions', []));
    }

    public function byGroup(): Collection
    {
        return $this->all()
            ->map(fn ($meta, $name) => array_merge($meta, ['name' => $name]))
            ->groupBy('group');
    }

    public function permissionMeta(string $name): ?array
    {
        return config('permissions.permissions', [])[$name] ?? null;
    }

    /**
     * Résout un nom (canonique ou alias) vers le nom canonique.
     * Retourne le nom inchangé si pas trouvé (compat).
     */
    public function canonicalize(string $name): string
    {
        if ($this->permissionMeta($name) !== null) {
            return $name;
        }

        return $this->aliasMap()[$name] ?? $name;
    }

    /**
     * Indique si $name est l'alias legacy d'une permission canonique.
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliasMap()[$name]);
    }

    /**
     * Indique si $name est une permission canonique connue.
     */
    public function isCanonical(string $name): bool
    {
        return $this->permissionMeta($name) !== null;
    }

    public function aliasesOf(string $canonical): array
    {
        return $this->permissionMeta($canonical)['aliases'] ?? [];
    }

    /**
     * Tous les noms (canoniques + aliases) qui doivent exister en DB.
     */
    public function allNames(): Collection
    {
        return $this->all()->keys()->merge(array_keys($this->aliasMap()))
            ->unique()->values();
    }

    /**
     * Map alias => canonical, exposée publiquement pour les outils d'audit
     * qui ont besoin de filtrer/inverser. Cachée à la première lecture.
     *
     * @return array<string, string>
     */
    public function aliasMap(): array
    {
        if ($this->aliasMapCache !== null) {
            return $this->aliasMapCache;
        }

        $map = [];
        foreach (config('permissions.permissions', []) as $canonical => $meta) {
            foreach ($meta['aliases'] ?? [] as $alias) {
                $map[$alias] = $canonical;
            }
        }

        return $this->aliasMapCache = $map;
    }

    /**
     * Permissions par défaut pour un rôle. Résout le wildcard '*' en toutes.
     */
    public function defaultPermissionsFor(string $role): array
    {
        $defaults = config('permissions.role_defaults', [])[$role] ?? [];

        if (in_array('*', $defaults, true)) {
            return $this->all()->keys()->all();
        }

        return $defaults;
    }

    /**
     * Liste des rôles que $actorRole peut gérer.
     */
    public function manageableRoles(string $actorRole): array
    {
        return config('permissions.role_management', [])[$actorRole] ?? [];
    }

    public function isDeprecated(string $name): bool
    {
        return isset(config('permissions.deprecated', [])[$name]);
    }

    public function deprecatedReason(string $name): ?string
    {
        return config('permissions.deprecated', [])[$name]['reason'] ?? null;
    }

    /**
     * Vide les caches internes (utile après création/édition d'un rôle custom
     * ou rechargement de config dans les tests).
     */
    public function clearCache(): void
    {
        $this->dbRolesCache = null;
        $this->aliasMapCache = null;
    }

    /**
     * Charge les rôles depuis la DB indexés par name. Tolère l'absence des
     * colonnes ajoutées par la migration Lot 8 (env. tests, fresh install).
     *
     * @return array<string, array{label_fr:?string, icon:?string, description:?string, is_custom:bool}>
     */
    private function dbRoles(): array
    {
        if ($this->dbRolesCache !== null) {
            return $this->dbRolesCache;
        }

        try {
            if (! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'is_custom')) {
                return $this->dbRolesCache = [];
            }

            $rows = Role::query()
                ->select(['name', 'label_fr', 'icon', 'description', 'is_custom'])
                ->get()
                ->keyBy('name')
                ->map(fn ($r) => [
                    'label_fr' => $r->label_fr ?? null,
                    'icon' => $r->icon ?? null,
                    'description' => $r->description ?? null,
                    'is_custom' => (bool) ($r->is_custom ?? false),
                ])
                ->all();

            return $this->dbRolesCache = $rows;
        } catch (Throwable $e) {
            // Test env sans DB / table absente → on degrade gracefully
            return $this->dbRolesCache = [];
        }
    }

    /**
     * Construit une collection des rôles custom au format identique au config
     * (label, description, icon, group, visible_in_ui, is_custom = true).
     */
    private function customRolesFromDb(): Collection
    {
        $configKeys = array_keys(config('permissions.roles', []));
        $custom = [];

        foreach ($this->dbRoles() as $name => $row) {
            if (! ($row['is_custom'] ?? false)) {
                continue;
            }
            // Si déjà dans le config (cas exotique), on laisse roleMeta() faire le merge
            if (in_array($name, $configKeys, true)) {
                continue;
            }
            $custom[$name] = [
                'label' => $row['label_fr'] ?? $name,
                'description' => $row['description'] ?? '',
                'icon' => $row['icon'] ?? 'fa-user-tag',
                'group' => 'Personnalisé',
                'visible_in_ui' => true,
                'is_custom' => true,
            ];
        }

        return collect($custom);
    }
}
