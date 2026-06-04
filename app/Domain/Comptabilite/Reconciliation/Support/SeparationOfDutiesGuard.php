<?php

namespace App\Domain\Comptabilite\Reconciliation\Support;

use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Helpers\SettingsHelper;
use App\Models\User;

/**
 * Helper séparation des devoirs OHADA.
 *
 * Si setting tenant `comptabilite.reconciliation.require_separation_of_duties` = true,
 * l'approbateur d'une session DOIT être différent de l'ouvreur. Sinon log warning.
 */
class SeparationOfDutiesGuard
{
    public static function assert(ReconciliationSession $session, User $approver, string $context = 'approve'): void
    {
        $required = (bool) SettingsHelper::get(
            'comptabilite.reconciliation.require_separation_of_duties',
            true
        );

        if ($session->opened_by === $approver->id) {
            if ($required) {
                throw new \DomainException(
                    "Séparation des devoirs OHADA : l'utilisateur qui a ouvert la session ne peut pas "
                    . "l'approuver/clôturer. Demandez à un autre comptable ou coordinateur."
                );
            }
            \Illuminate\Support\Facades\Log::warning('Réconciliation : séparation des devoirs contournée (config tenant)', [
                'session_id' => $session->id,
                'session_code' => $session->code,
                'user_id' => $approver->id,
                'context' => $context,
            ]);
        }
    }
}
