<?php

namespace App\Domain\Comptabilite\Reconciliation\Services;

use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Models\ESBTPPaiement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service drill-down : retourne les paiements validés contribuant au montant_systeme
 * d'un (session, mode_paiement). Permet au comptable de pointer ligne par ligne
 * KLASSCI vs portail merchant (Orange Money, MTN MoMo, etc.).
 *
 * Rule no-god-code-compta : extrait du controller — controller orchestration only.
 */
class PaymentDrillDownService
{
    public function paginate(ReconciliationSession $session, string $modePaiement, int $perPage = 20): LengthAwarePaginator
    {
        return ESBTPPaiement::query()
            ->whereNull('deleted_at')
            ->where('status', 'validé')
            ->where('mode_paiement', $modePaiement)
            ->whereBetween('date_paiement', [$session->period_start, $session->period_end])
            ->with(['etudiant:id,nom,prenoms,matricule'])
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function totals(ReconciliationSession $session, string $modePaiement): array
    {
        $aggs = ESBTPPaiement::query()
            ->whereNull('deleted_at')
            ->where('status', 'validé')
            ->where('mode_paiement', $modePaiement)
            ->whereBetween('date_paiement', [$session->period_start, $session->period_end])
            ->selectRaw('COUNT(*) as nb, COALESCE(SUM(montant), 0) as total')
            ->first();

        return [
            'count' => (int) ($aggs->nb ?? 0),
            'total_amount' => (float) ($aggs->total ?? 0),
        ];
    }
}
