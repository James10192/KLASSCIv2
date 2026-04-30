<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Lecture du registry config/permissions.php.
 *
 * Source unique de vérité pour rôles, permissions, aliases, défauts et
 * matrice de gestion. À utiliser partout au lieu d'accéder directement au config.
 */
class PermissionRegistry
{
    /** Cache des aliases inversés : alias => canonical */
    private ?array $aliasMap = null;

    public function roles(): Collection
    {
        return collect(config('permissions.roles', []));
    }

    public function rolesVisibleInUi(): Collection
    {
        return $this->roles()->filter(fn ($meta) => ($meta['visible_in_ui'] ?? true) === true);
    }

    public function roleMeta(string $role): ?array
    {
        return config('permissions.roles', [])[$role] ?? null;
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

    public function aliasesOf(string $canonical): array
    {
        return $this->permissionMeta($canonical)['aliases'] ?? [];
    }

    /**
     * Tous les noms (canoniques + aliases) qui doivent exister en DB.
     */
    public function allNames(): Collection
    {
        return $this->all()->keys()->merge(
            $this->all()->flatMap(fn ($meta) => $meta['aliases'] ?? [])
        )->unique()->values();
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
     * Map alias => canonical, calculé à la demande puis caché.
     */
    private function aliasMap(): array
    {
        if ($this->aliasMap !== null) {
            return $this->aliasMap;
        }

        $map = [];
        foreach (config('permissions.permissions', []) as $canonical => $meta) {
            foreach ($meta['aliases'] ?? [] as $alias) {
                $map[$alias] = $canonical;
            }
        }

        return $this->aliasMap = $map;
    }
}
