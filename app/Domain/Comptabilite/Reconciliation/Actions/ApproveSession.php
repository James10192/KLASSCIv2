<?php

namespace App\Domain\Comptabilite\Reconciliation\Actions;

use App\Domain\Comptabilite\Reconciliation\Events\ReconciliationApproved;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Domain\Comptabilite\Reconciliation\Support\SeparationOfDutiesGuard;
use App\Enums\ReconciliationSessionStatus;
use App\Models\User;

class ApproveSession
{
    public function execute(ReconciliationSession $session, User $user): ReconciliationSession
    {
        if (!$session->status->canTransitionTo(ReconciliationSessionStatus::APPROVED)) {
            throw new \DomainException("Transition non autorisée depuis {$session->status->value}.");
        }

        SeparationOfDutiesGuard::assert($session, $user, 'approve');

        $session->update([
            'status' => ReconciliationSessionStatus::APPROVED->value,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $session->refresh();
        event(new ReconciliationApproved($session));
        return $session;
    }
}
