<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lecture du catalogue de widgets dashboard (config/dashboard_widgets.php).
 *
 * Source unique de vérité pour les widgets disponibles + résolution du layout
 * par utilisateur (preferences custom OU défauts basés sur les rôles).
 *
 * À utiliser via injection ou container :
 *   $registry = app(DashboardWidgetRegistry::class);
 *   $layout = $registry->userLayout(auth()->user());
 */
class DashboardWidgetRegistry
{
    /**
     * Tous les widgets définis dans config/dashboard_widgets.php, indexés par clé.
     */
    public function all(): Collection
    {
        return collect(config('dashboard_widgets', []))
            ->map(fn ($widget, $key) => array_merge(['key' => $key], $widget));
    }

    /**
     * Widgets pour lesquels $user a la permission requise.
     *
     * Filtre par permission canonique (ou alias). Préserve l'ordre du config.
     */
    public function availableFor(User $user): Collection
    {
        return $this->all()->filter(function ($widget) use ($user) {
            $permission = $widget['permission'] ?? null;
            if (empty($permission)) {
                return true;
            }

            return $user->can($permission);
        });
    }

    /**
     * Layout effectif pour un utilisateur :
     * - Si user.dashboard_widgets = NULL → défauts basés sur ses rôles
     * - Sinon → liste explicite (préserve l'ordre, filtre les widgets désactivés
     *   ou révoqués par perte de permission)
     *
     * Retourne une Collection ordonnée de widgets (chacun avec sa clé).
     */
    public function userLayout(User $user): Collection
    {
        $available = $this->availableFor($user);
        $userPrefs = $user->dashboard_widgets;

        // Aucune préférence : tomber sur les défauts par rôle
        if ($userPrefs === null) {
            return $this->defaultLayoutFor($user, $available);
        }

        // Layout explicite : preserve l'ordre, ne garde que les widgets enabled
        // ET disponibles (perm OK + clé existe)
        return collect($userPrefs)
            ->filter(fn ($entry) => is_array($entry) && ($entry['enabled'] ?? true))
            ->map(fn ($entry) => $available->get($entry['key'] ?? null))
            ->filter()
            ->values();
    }

    /**
     * Widgets activés par défaut pour les rôles d'un user (utilisés quand
     * user.dashboard_widgets = NULL).
     */
    public function defaultLayoutFor(User $user, ?Collection $available = null): Collection
    {
        $available ??= $this->availableFor($user);
        $roleNames = $user->roles->pluck('name')->all();

        return $available->filter(function ($widget) use ($roleNames) {
            $widgetRoles = $widget['default_for_roles'] ?? [];

            return ! empty(array_intersect($roleNames, $widgetRoles));
        })->values();
    }

    /**
     * Widgets par défaut pour un rôle nommé (helper, utilisé en dehors d'un
     * contexte user — ex: documentation, tests).
     */
    public function defaultsForRole(string $role): Collection
    {
        return $this->all()->filter(function ($widget) use ($role) {
            $widgetRoles = $widget['default_for_roles'] ?? [];

            return in_array($role, $widgetRoles, true);
        })->values();
    }

    /**
     * Widgets groupés par leur clé `group` (pour la modal de configuration).
     */
    public function availableGroupedFor(User $user): Collection
    {
        return $this->availableFor($user)
            ->groupBy(fn ($widget) => $widget['group'] ?? 'Autres');
    }

    /**
     * Vérifie qu'une clé widget existe dans le catalogue.
     */
    public function exists(string $key): bool
    {
        return $this->all()->has($key);
    }

    /**
     * Construit un layout final à partir d'une sélection (clés actives, ordre).
     *
     * Sanitize les entrées soumises depuis l'UI :
     * - Garde uniquement les clés du catalogue
     * - Garde uniquement les widgets pour lesquels $user a la permission
     * - Préserve l'ordre fourni
     *
     * @param  list<string>  $orderedKeys  Liste ordonnée des clés à activer
     */
    public function buildLayoutPayload(User $user, array $orderedKeys): array
    {
        $available = $this->availableFor($user);

        return collect($orderedKeys)
            ->filter(fn ($key) => is_string($key) && $available->has($key))
            ->unique()
            ->values()
            ->map(fn ($key) => ['key' => $key, 'enabled' => true])
            ->all();
    }
}
