<?php

namespace App\Domain\Comptabilite\Reconciliation\Actions;

use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Enums\ReconciliationSessionStatus;
use App\Models\User;

class ReviewSession
{
    public function execute(ReconciliationSession $session, User $user): ReconciliationSession
    {
        if (!$session->status->canTransitionTo(ReconciliationSessionStatus::REVIEW)) {
            throw new \DomainException("Transition non autorisée depuis {$session->status->value}.");
        }

        // Pré-requis : toutes les discrepancies doivent être résolues ou rejetées
        $pending = $session->discrepancies()->where('action', 'a_traiter')->count();
        if ($pending > 0) {
            throw new \DomainException("{$pending} écart(s) non traités. Résoudre avant passage en revue.");
        }

        $session->update([
            'status' => ReconciliationSessionStatus::REVIEW->value,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        return $session->refresh();
    }
}
