<?php

namespace App\Domain\Analytics\Repositories;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Models\ESBTPPaiement;

/**
 * Data access centralisé pour Analytics. Single GROUP BY queries (anti N+1),
 * filtré par AnalyticsContext.
 */
class AnalyticsRepository
{
    /**
     * Recettes mensuelles validées sur les N derniers mois, filtrées.
     *
     * @return array<int, array{year: int, month: int, value: float}>
     */
    public function monthlyRevenue(AnalyticsContext $context, int $months = 24): array
    {
        $rows = ESBTPPaiement::query()
            ->where('status', 'validé')
            ->whereNull('deleted_at')
            ->where('date_paiement', '>=', now()->subMonths($months)->startOfMonth())
            ->when($context->anneeId, fn ($q) => $q->whereHas('inscription', fn ($q2) => $q2->where('annee_universitaire_id', $context->anneeId)))
            ->when($context->filiereId, fn ($q) => $q->whereHas('inscription.classe', fn ($q2) => $q2->where('filiere_id', $context->filiereId)))
            ->when($context->classeId, fn ($q) => $q->whereHas('inscription', fn ($q2) => $q2->where('classe_id', $context->classeId)))
            ->selectRaw('YEAR(date_paiement) as year, MONTH(date_paiement) as month, SUM(montant) as value')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($r) => [
            'year' => (int) $r->year,
            'month' => (int) $r->month,
            'value' => (float) $r->value,
        ])->all();
    }
}
