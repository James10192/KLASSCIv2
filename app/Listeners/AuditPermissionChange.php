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
 * Les modifications réelles que l'on veut tracer sont les ATTACH/DETACH
 * sur la pivot (`model_has_roles`, `model_has_permissions`, `role_has_permissions`),
 * pas seulement les CRUD sur les modèles Role/Permission eux-mêmes.
 *
 * Spatie 5.11 (version installée) ne dispatche pas d'événements natifs
 * `RoleAttached` / `PermissionAttached`. On s'appuie donc sur les Eloquent
 * pivot events `Eloquent.pivotAttached` / `pivotDetached` pour les couvrir.
 *
 * Référence : https://github.com/spatie/laravel-permission/issues
 *
 * Inscription : voir EventServiceProvider::boot() qui appelle
 * Event::listen('eloquent.pivotAttached: App\Models\User', ...).
 */
class AuditPermissionChange
{
    /**
     * Handler pour `Eloquent.pivotAttached: <Model>`.
     *
     * Signature Eloquent : ($model, $relationName, $pivotIds, $pivotIdsAttributes).
     */
    public function handlePivotAttached($event, array $payload = []): void
    {
        // Selon la signature de listen, soit le 1er param est l'event-name string
        // (avec $payload = [model, relation, ids, attrs]), soit l'event lui-même.
        [$model, $relationName, $pivotIds] = $this->normalize($event, $payload);

        if (! $this->isPermissionRelation($relationName)) {
            return;
        }

        $this->writeAudit($model, 'attached', $relationName, $pivotIds);
    }

    /**
     * Handler pour `Eloquent.pivotDetached: <Model>`.
     */
    public function handlePivotDetached($event, array $payload = []): void
    {
        [$model, $relationName, $pivotIds] = $this->normalize($event, $payload);

        if (! $this->isPermissionRelation($relationName)) {
            return;
        }

        $this->writeAudit($model, 'detached', $relationName, $pivotIds);
    }

    /**
     * Handler générique CRUD sur Role / Permission (créer/modifier/supprimer un rôle).
     *
     * Branché via Role::observe() et Permission::observe() dans EventServiceProvider.
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
     * Écrit l'entrée dans la table `audits` pour un attach/detach.
     */
    private function writeAudit($model, string $event, string $relationName, array $pivotIds): void
    {
        try {
            $relationLabels = [
                'attached' => 'attaché',
                'detached' => 'détaché',
            ];

            // On utilise auditable_type/_id pointant sur le model parent
            // (l'utilisateur qui reçoit/perd le rôle, ou le rôle qui reçoit/perd la perm).
            Audit::create([
                'user_type' => \App\Models\User::class,
                'user_id' => auth()->id(),
                'event' => $event,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'old_values' => null,
                'new_values' => json_encode([
                    'relation' => $relationName,
                    'pivot_ids' => $pivotIds,
                    'label' => ($relationLabels[$event] ?? $event) . ' ' . $relationName,
                ]),
                'url' => request() ? request()->fullUrl() : null,
                'ip_address' => request() ? request()->ip() : null,
                'user_agent' => request() ? request()->userAgent() : null,
                'tags' => 'permissions',
            ]);
        } catch (\Throwable $e) {
            Log::warning('AuditPermissionChange: échec écriture audit pivot', [
                'event' => $event,
                'relation' => $relationName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Écrit l'entrée pour un CRUD direct sur Role ou Permission.
     */
    private function writeRolePermissionCrud($model, string $event): void
    {
        try {
            Audit::create([
                'user_type' => \App\Models\User::class,
                'user_id' => auth()->id(),
                'event' => $event,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'old_values' => $event === 'updated' ? json_encode($model->getOriginal()) : null,
                'new_values' => json_encode($model->getAttributes()),
                'url' => request() ? request()->fullUrl() : null,
                'ip_address' => request() ? request()->ip() : null,
                'user_agent' => request() ? request()->userAgent() : null,
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

    /**
     * Filtre : on ne s'intéresse qu'aux relations roles/permissions Spatie.
     */
    private function isPermissionRelation(string $relationName): bool
    {
        return in_array($relationName, ['roles', 'permissions'], true);
    }

    /**
     * Normalise les arguments — Laravel passe les pivot events soit avec
     * le nom de l'event en 1er, soit avec les arguments directement.
     *
     * @return array [model, relationName, pivotIds]
     */
    private function normalize($event, array $payload): array
    {
        // Si event est une string (event name), payload contient les vrais args
        if (is_string($event)) {
            return [$payload[0] ?? null, $payload[1] ?? '', $payload[2] ?? []];
        }

        // Si event est l'objet model directement (passé par func_get_args dans le closure)
        return [$event, $payload[0] ?? '', $payload[1] ?? []];
    }
}
