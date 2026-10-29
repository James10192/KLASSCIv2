<?php

namespace App\Services\Trash;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Service partagé pour la corbeille multi-entité (sous-lot C+).
 *
 * Résout les métadonnées de suppression depuis la table OwenIt audits :
 *  - qui a supprimé (user_id + name)
 *  - quand (deleted_at de l'entity)
 *  - temps écoulé (relative time human-readable)
 *
 * Utilisé par ESBTPEtudiantTrashController, ESBTPInscriptionTrashController,
 * ESBTPPaiementTrashController pour éviter la duplication.
 */
class TrashAuditService
{
    /**
     * Charge en batch les infos "deleter" pour une collection de models soft-deleted.
     * Retourne une map keyed par model id : { user_id, user_name, deleted_at_carbon, ago_human }.
     */
    public function batchDeleters(string $auditableType, Collection $models): array
    {
        if ($models->isEmpty()) {
            return [];
        }

        $ids = $models->pluck('id');

        // Pour chaque entity, on prend le DERNIER audit event='deleted'.
        // Sub-query qui groupe par auditable_id et prend le MAX(id) (dernier audit).
        $audits = DB::table('audits')
            ->select('audits.auditable_id', 'audits.user_id', 'audits.created_at', 'users.name as user_name')
            ->joinSub(
                DB::table('audits')
                    ->select('auditable_id', DB::raw('MAX(id) as last_audit_id'))
                    ->where('auditable_type', $auditableType)
                    ->where('event', 'deleted')
                    ->whereIn('auditable_id', $ids)
                    ->groupBy('auditable_id'),
                'latest_deletes',
                fn ($join) => $join->on('audits.id', '=', 'latest_deletes.last_audit_id')
            )
            ->leftJoin('users', 'audits.user_id', '=', 'users.id')
            ->get()
            ->keyBy('auditable_id');

        $now = Carbon::now();
        $out = [];
        foreach ($models as $model) {
            $audit = $audits->get($model->id);
            $deletedAt = $model->deleted_at instanceof Carbon ? $model->deleted_at : ($model->deleted_at ? Carbon::parse($model->deleted_at) : null);

            $out[$model->id] = [
                'user_id' => $audit?->user_id,
                'user_name' => $audit?->user_name ?? '—',
                'deleted_at' => $deletedAt,
                'ago_human' => $deletedAt ? $deletedAt->diffForHumans($now, true, false) : null,
                'days_in_trash' => $deletedAt ? (int) $deletedAt->diffInDays($now) : null,
            ];
        }

        return $out;
    }

    /**
     * Compte les soft-deleted groupés par tranche temporelle (cette semaine / > 30j / total).
     */
    public function bucketsByAge(string $modelClass): array
    {
        /** @var class-string<Model> $modelClass */
        $now = Carbon::now();
        $weekAgo = $now->copy()->subDays(7);
        $thirtyDaysAgo = $now->copy()->subDays(30);

        $base = $modelClass::onlyTrashed();

        return [
            'total' => (clone $base)->count(),
            'this_week' => (clone $base)->where('deleted_at', '>=', $weekAgo)->count(),
            'older_than_30' => (clone $base)->where('deleted_at', '<', $thirtyDaysAgo)->count(),
        ];
    }
}
