<?php

namespace App\Domain\Analytics\Quality;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Agregats de saisie des paiements pour PaymentQualityEvaluator.
 * Une seule requete groupee sur la fenetre, jamais une ligne de paiement
 * chargee en memoire.
 */
class PaymentQualityQuery
{
    /**
     * @return array<int, array{jour: string, user_id: ?int, nombre: int, anciens: int}>
     */
    public function entryGroups(CarbonImmutable $since, int $lagDays): array
    {
        return DB::table('esbtp_paiements')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as jour, created_by as user_id, COUNT(*) as nombre')
            ->selectRaw('SUM(CASE WHEN DATEDIFF(created_at, date_paiement) > ? THEN 1 ELSE 0 END) as anciens', [$lagDays])
            ->groupByRaw('DATE(created_at), created_by')
            ->get()
            ->map(fn ($row) => [
                'jour' => (string) $row->jour,
                'user_id' => $row->user_id !== null ? (int) $row->user_id : null,
                'nombre' => (int) $row->nombre,
                'anciens' => (int) $row->anciens,
            ])
            ->all();
    }

    public function lastEntryAt(): ?CarbonImmutable
    {
        $last = DB::table('esbtp_paiements')->whereNull('deleted_at')->max('created_at');

        return $last ? CarbonImmutable::parse($last) : null;
    }
}
