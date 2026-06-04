<?php

namespace App\Domain\Comptabilite\Reconciliation\Actions;

use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Enums\ReconciliationSessionStatus;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ReopenSession
{
    public function execute(ReconciliationSession $session, User $user, string $reason): ReconciliationSession
    {
        if (!$session->status->canTransitionTo(ReconciliationSessionStatus::REOPENED)) {
            throw new \DomainException("Transition non autorisée depuis {$session->status->value}.");
        }
        if (strlen($reason) < 30) {
            throw new \InvalidArgumentException('Motif de réouverture obligatoire (≥ 30 caractères).');
        }
        if (!$user->can('comptabilite.reconciliation.bypass_lock')) {
            throw new \DomainException('Permission manquante : comptabilite.reconciliation.bypass_lock');
        }

        Log::warning('Réconciliation : session rouverte (exception)', [
            'session_id' => $session->id,
            'session_code' => $session->code,
            'reopened_by' => $user->id,
            'reason' => $reason,
        ]);

        // Déverrouille les paiements liés (cascade inverse de LockPaymentsAfterReconciliation)
        $session->lockedPaiements()->update([
            'reconciliation_locked_at' => null,
            'last_reconciliation_session_id' => null,
        ]);

        $session->update([
            'status' => ReconciliationSessionStatus::REOPENED->value,
            'reopened_by' => $user->id,
            'reopened_at' => now(),
            'reopen_reason' => $reason,
        ]);

        return $session->refresh();
    }
}
