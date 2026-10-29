<?php

namespace App\Domain\Comptabilite\Reconciliation\Services;

use App\Domain\Comptabilite\Reconciliation\Models\CashCount;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Helpers\SettingsHelper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Métriques santé du module réconciliation (PR6).
 *
 * Rule no-god-code-compta : extraction Service Domain, controller orchestration only.
 *
 * Métriques calculées :
 * - sessions_by_status : nombre par status (draft/review/approved/closed/reopened)
 * - overdue_draft_count : sessions draft ouvertes depuis > N jours (configurable)
 * - days_since_last_close
 * - avg_days_to_close (sur les 90 derniers jours)
 * - pct_sessions_no_ecart (sur les sessions clôturées des 90 derniers jours)
 * - avg_ecart_by_mode (sur les 90 derniers jours)
 * - health_status : ok | warning | degraded
 */
class ReconciliationMetricsService
{
    public function snapshot(): array
    {
        $now = CarbonImmutable::now();
        $overdueDays = (int) SettingsHelper::get('comptabilite.reconciliation.overdue_days', 2);
        $overdueThreshold = $now->subDays($overdueDays);

        $byStatus = ReconciliationSession::query()
            ->selectRaw('status, COUNT(*) as nb')
            ->groupBy('status')
            ->pluck('nb', 'status')
            ->all();

        $overdueDraft = ReconciliationSession::query()
            ->where('status', 'draft')
            ->where('opened_at', '<', $overdueThreshold)
            ->count();

        $lastClosed = ReconciliationSession::query()
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->first(['id', 'code', 'closed_at']);

        $daysSinceLastClose = $lastClosed?->closed_at
            ? $now->diffInDays($lastClosed->closed_at)
            : null;

        // Métriques sur 90 derniers jours (durée raisonnable contre noise)
        $cutoff90d = $now->subDays(90);
        $closedLast90 = ReconciliationSession::query()
            ->where('status', 'closed')
            ->where('closed_at', '>=', $cutoff90d)
            ->get(['id', 'opened_at', 'closed_at']);

        $avgDaysToClose = $closedLast90->isEmpty()
            ? null
            : round($closedLast90->avg(fn (ReconciliationSession $s) => $s->opened_at->diffInDays($s->closed_at)), 1);

        $noEcartPct = null;
        if ($closedLast90->isNotEmpty()) {
            $totalClosed = $closedLast90->count();
            $sessionsNoEcart = $closedLast90->filter(fn (ReconciliationSession $s) => abs($s->totalEcart()) < 0.01)->count();
            $noEcartPct = $totalClosed > 0 ? round(($sessionsNoEcart / $totalClosed) * 100, 1) : null;
        }

        $avgEcartByMode = CashCount::query()
            ->whereHas('session', fn ($q) => $q->where('status', 'closed')->where('closed_at', '>=', $cutoff90d))
            ->selectRaw('mode_paiement, AVG(montant_compte - montant_systeme) as avg_ecart, COUNT(*) as nb')
            ->groupBy('mode_paiement')
            ->orderBy('mode_paiement')
            ->get()
            ->map(fn ($r) => [
                'mode' => $r->mode_paiement,
                'avg_ecart' => round((float) $r->avg_ecart, 2),
                'sample_size' => (int) $r->nb,
            ])
            ->all();

        $healthStatus = $this->resolveHealthStatus($overdueDraft, $daysSinceLastClose);

        return [
            'sessions_by_status' => [
                'draft' => (int) ($byStatus['draft'] ?? 0),
                'review' => (int) ($byStatus['review'] ?? 0),
                'approved' => (int) ($byStatus['approved'] ?? 0),
                'closed' => (int) ($byStatus['closed'] ?? 0),
                'reopened' => (int) ($byStatus['reopened'] ?? 0),
                'total' => array_sum($byStatus),
            ],
            'overdue_draft_count' => $overdueDraft,
            'overdue_threshold_days' => $overdueDays,
            'last_close_code' => $lastClosed?->code,
            'last_close_at' => $lastClosed?->closed_at?->toIso8601String(),
            'days_since_last_close' => $daysSinceLastClose,
            'avg_days_to_close_90d' => $avgDaysToClose,
            'pct_sessions_no_ecart_90d' => $noEcartPct,
            'avg_ecart_by_mode_90d' => $avgEcartByMode,
            'health_status' => $healthStatus,
            'computed_at' => $now->toIso8601String(),
        ];
    }

    /**
     * Retourne les sessions overdue (status active depuis > overdueDays jours).
     *
     * @return \Illuminate\Support\Collection<int,ReconciliationSession>
     */
    public function overdueSessions(): \Illuminate\Support\Collection
    {
        $overdueDays = (int) SettingsHelper::get('comptabilite.reconciliation.overdue_days', 2);
        $threshold = CarbonImmutable::now()->subDays($overdueDays);

        return ReconciliationSession::query()
            ->whereIn('status', ['draft', 'review', 'approved'])
            ->where('opened_at', '<', $threshold)
            ->with('opener:id,name,email')
            ->orderBy('opened_at')
            ->get();
    }

    private function resolveHealthStatus(int $overdueDraft, ?int $daysSinceLastClose): string
    {
        if ($overdueDraft > 0) {
            return 'degraded';
        }
        if ($daysSinceLastClose !== null && $daysSinceLastClose > 7) {
            return 'warning';
        }
        return 'ok';
    }
}
