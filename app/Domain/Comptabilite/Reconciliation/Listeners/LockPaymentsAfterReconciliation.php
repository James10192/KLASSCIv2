<?php

namespace App\Domain\Comptabilite\Reconciliation\Listeners;

use App\Domain\Comptabilite\Reconciliation\Events\ReconciliationClosed;
use App\Models\ESBTPPaiement;

/**
 * Quand une session est clôturée :
 * - Verrouille tous les paiements de la période fenêtre [period_start, period_end]
 *   qui ont le mode_paiement couvert par un cash_count de la session.
 * - reconciliation_locked_at = closed_at
 * - last_reconciliation_session_id = session.id
 *
 * Désormais ces paiements ne peuvent plus être modifiés sauf permission
 * comptabilite.reconciliation.bypass_lock.
 */
class LockPaymentsAfterReconciliation
{
    public function handle(ReconciliationClosed $event): void
    {
        $session = $event->session;
        $modes = $session->cashCounts->pluck('mode_paiement')->unique()->all();

        if (empty($modes)) {
            return;
        }

        ESBTPPaiement::query()
            ->whereNull('deleted_at')
            ->where('status', 'validé')
            ->whereIn('mode_paiement', $modes)
            ->whereBetween('date_paiement', [$session->period_start, $session->period_end])
            ->whereNull('reconciliation_locked_at')
            ->update([
                'reconciliation_locked_at' => $session->closed_at ?? now(),
                'last_reconciliation_session_id' => $session->id,
            ]);
    }
}
