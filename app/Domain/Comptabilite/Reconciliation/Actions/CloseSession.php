<?php

namespace App\Domain\Comptabilite\Reconciliation\Actions;

use App\Domain\Comptabilite\Reconciliation\Events\ReconciliationClosed;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Enums\ReconciliationSessionStatus;
use App\Models\User;

class CloseSession
{
    public function execute(ReconciliationSession $session, User $user): ReconciliationSession
    {
        if (!$session->status->canTransitionTo(ReconciliationSessionStatus::CLOSED)) {
            throw new \DomainException("Transition non autorisée depuis {$session->status->value}.");
        }

        $session->update([
            'status' => ReconciliationSessionStatus::CLOSED->value,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        $session->refresh();
        event(new ReconciliationClosed($session));
        return $session;
    }
}
