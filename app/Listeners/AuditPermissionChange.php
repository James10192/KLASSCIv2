<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Listener pour tracer les changements rôles/permissions Spatie.
 *
 * Pourquoi un listener custom et pas le trait Auditable sur Role/Permission ?
 * Les modifications réelles que l'on veut tracer sont les ATTACH/DETACH sur la
 * pivot (`model_has_roles`, `model_has_permissions`, `role_has_permissions`),
 * pas seulement les CRUD sur les modèles Role/Permission eux-mêmes.
 *
 * Spatie 5.11 (version installée) ne dispatche pas d'événements natifs
 * `RoleAttached` / `PermissionAttached`. On s'appuie donc sur les Eloquent
 * pivot events `eloquent.pivotAttached` / `pivotDetached` pour les couvrir.
 *
 * Inscription : voir `EventServiceProvider::boot()` qui appelle
 * `Event::listen('eloquent.pivotAttached: App\Models\User', ...)`.
 */
class AuditPermissionChange
{
    /**
     * Map event → label français (mémoïsé en const).
     */
    private const EVENT_LABELS = [
        'attached' => 'attaché',
        'detached' => 'détaché',
    ];

    /**
     * Handler pour `eloquent.pivotAttached: <Model>`.
     *
     * Signature Eloquent (via Event::listen closure) :
     * ($model, $relationName, $pivotIds, $pivotIdsAttributes).
     */
    public function handlePivotAttached($model, array $payload = []): void
    {
        $this->handlePivot('attached', $model, $payload);
    }

    /**
     * Handler pour `eloquent.pivotDetached: <Model>`.
     */
    public function handlePivotDetached($model, array $payload = []): void
    {
        $this->handlePivot('detached', $model, $payload);
    }

    /**
     * Handler générique CRUD sur Role / Permission (créer/modifier/supprimer un rôle).
     *
     * Branché via `Role::saved()` et `Permission::saved()` dans EventServiceProvider.
     */
    public function handleRoleSaved(Role $role): void
    {
        $this->writeRolePermissionCrud($role, $role->wasRecentlyCreated ? 'created' : 'updated');
    }

    public function handleRoleDeleted(Role $role): void
    {
        $this->writeRolePermissionCrud($role, 'deleted');
    }

    public function handlePermissionSaved(Permission $permission): void
    {
        $this->writeRolePermissionCrud($permission, $permission->wasRecentlyCreated ? 'created' : 'updated');
    }

    public function handlePermissionDeleted(Permission $permission): void
    {
        $this->writeRolePermissionCrud($permission, 'deleted');
    }

    /**
     * Routine commune attach/detach.
     */
    private function handlePivot(string $event, $model, array $payload): void
    {
        // Dans EventServiceProvider on passe (model, [relation, ids, attrs]).
        // On supporte aussi le mode où Laravel passe (eventName, [model, relation, ids, ...]).
        if (is_string($model)) {
            [$model, $relationName, $pivotIds] = [
                $payload[0] ?? null,
                $payload[1] ?? '',
                $payload[2] ?? [],
            ];
        } else {
            $relationName = $payload[0] ?? '';
            $pivotIds = $payload[1] ?? [];
        }

        if (! in_array($relationName, ['roles', 'permissions'], true)) {
            return;
        }

        $this->writeAudit($event, $model, [
            'relation' => $relationName,
            'pivot_ids' => $pivotIds,
            'label' => (self::EVENT_LABELS[$event] ?? $event) . ' ' . $relationName,
        ]);
    }

    /**
     * Écrit l'entrée dans la table `audits` pour un attach/detach.
     */
    private function writeAudit(string $event, $model, array $newValues): void
    {
        $request = request();

        try {
            Audit::create([
                'user_type' => \App\Models\User::class,
                'user_id' => auth()->id(),
                'event' => $event,
                'auditable_type' => $model ? get_class($model) : null,
                'auditable_id' => $model?->getKey(),
                'old_values' => null,
                'new_values' => json_encode($newValues),
                'url' => $request?->fullUrl(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'tags' => 'permissions',
            ]);
        } catch (\Throwable $e) {
            Log::warning('AuditPermissionChange: échec écriture audit pivot', [
                'event' => $event,
                'relation' => $newValues['relation'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Écrit l'entrée pour un CRUD direct sur Role ou Permission.
     */
    private function writeRolePermissionCrud($model, string $event): void
    {
        $request = request();

        try {
            Audit::create([
                'user_type' => \App\Models\User::class,
                'user_id' => auth()->id(),
                'event' => $event,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'old_values' => $event === 'updated' ? json_encode($model->getOriginal()) : null,
                'new_values' => json_encode($model->getAttributes()),
                'url' => $request?->fullUrl(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'tags' => 'permissions',
            ]);
        } catch (\Throwable $e) {
            Log::warning('AuditPermissionChange: échec écriture audit CRUD', [
                'event' => $event,
                'model' => get_class($model),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
