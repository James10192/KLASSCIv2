<?php

namespace App\Domain\Comptabilite\Reconciliation\Actions;

use App\Domain\Comptabilite\Reconciliation\Events\PaymentAdjusted;
use App\Domain\Comptabilite\Reconciliation\Models\PaymentReconciliationLog;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationDiscrepancy;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Action orchestrant les 4 sous-actions de résolution d'écart :
 * - adjust_payment   : modifie un paiement existant (montant/mode/date/motif)
 * - create_corrective: crée un paiement correctif lié au discrepancy
 * - cancel_payment   : passe un paiement existant en status='rejeté'
 * - no_action        : accepte l'écart avec motif documenté
 *
 * Toute mutation crée une PaymentReconciliationLog row immutable (snapshot before/after).
 */
class ResolveDiscrepancy
{
    public function execute(
        ReconciliationDiscrepancy $discrepancy,
        User $user,
        string $resolutionType,
        string $motif,
        array $payload = []
    ): ReconciliationDiscrepancy {
        if (!$discrepancy->session->isModifiable()) {
            throw new \DomainException("Session {$discrepancy->session->code} non modifiable.");
        }
        if (strlen($motif) < 10) {
            throw new \InvalidArgumentException('Motif obligatoire (≥ 10 caractères).');
        }
        if (!in_array($resolutionType, ['adjust_payment', 'create_corrective', 'cancel_payment', 'no_action'], true)) {
            throw new \InvalidArgumentException("Resolution type invalide : {$resolutionType}");
        }

        return DB::transaction(function () use ($discrepancy, $user, $resolutionType, $motif, $payload) {
            $resolutionPayment = match ($resolutionType) {
                'adjust_payment' => $this->adjustPayment($discrepancy, $user, $motif, $payload),
                'create_corrective' => $this->createCorrective($discrepancy, $user, $motif, $payload),
                'cancel_payment' => $this->cancelPayment($discrepancy, $user, $motif),
                'no_action' => null,
            };

            $discrepancy->update([
                'action' => 'resolu',
                'resolution_type' => $resolutionType,
                'resolution_payment_id' => $resolutionPayment?->id,
                'motif' => $motif,
                'resolved_by' => $user->id,
                'resolved_at' => now(),
            ]);

            return $discrepancy->refresh();
        });
    }

    private function adjustPayment(
        ReconciliationDiscrepancy $discrepancy,
        User $user,
        string $motif,
        array $payload
    ): ESBTPPaiement {
        $paiement = ESBTPPaiement::lockForUpdate()->findOrFail($discrepancy->paiement_concerne_id);
        $before = $paiement->toArray();

        $delta = [];
        foreach (['montant', 'mode_paiement', 'date_paiement', 'motif'] as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== $paiement->{$field}) {
                $delta[$field] = ['from' => $paiement->{$field}, 'to' => $payload[$field]];
                $paiement->{$field} = $payload[$field];
            }
        }

        if (!empty($delta)) {
            $paiement->updated_by = $user->id;
            $paiement->save();

            $this->log($discrepancy->session, $paiement, 'adjust_montant', $before, $paiement->fresh()->toArray(), $delta, $motif, $user);
            event(new PaymentAdjusted($paiement, $discrepancy->session, $delta));
        }

        return $paiement;
    }

    private function createCorrective(
        ReconciliationDiscrepancy $discrepancy,
        User $user,
        string $motif,
        array $payload
    ): ESBTPPaiement {
        $paiement = ESBTPPaiement::create([
            'etudiant_id' => $payload['etudiant_id'] ?? null,
            'inscription_id' => $payload['inscription_id'] ?? null,
            'annee_universitaire_id' => $payload['annee_universitaire_id'] ?? $discrepancy->session->annee_universitaire_id,
            'montant' => $payload['montant'] ?? abs((float) $discrepancy->montant_ecart),
            'mode_paiement' => $payload['mode_paiement'] ?? null,
            'motif' => "[Correctif réconciliation {$discrepancy->session->code}] {$motif}",
            'date_paiement' => $payload['date_paiement'] ?? now()->toDateString(),
            'status' => 'validé',
            'created_by' => $user->id,
            'validated_by' => $user->id,
            'date_validation' => now(),
        ]);

        $this->log($discrepancy->session, $paiement, 'create', [], $paiement->toArray(), ['create' => true], $motif, $user);
        return $paiement;
    }

    private function cancelPayment(
        ReconciliationDiscrepancy $discrepancy,
        User $user,
        string $motif
    ): ESBTPPaiement {
        $paiement = ESBTPPaiement::lockForUpdate()->findOrFail($discrepancy->paiement_concerne_id);
        $before = $paiement->toArray();
        $paiement->status = 'rejeté';
        $paiement->updated_by = $user->id;
        $paiement->save();

        $this->log($discrepancy->session, $paiement, 'cancel', $before, $paiement->fresh()->toArray(), ['status' => ['from' => $before['status'], 'to' => 'rejeté']], $motif, $user);
        return $paiement;
    }

    private function log(
        ReconciliationSession $session,
        ESBTPPaiement $paiement,
        string $actionType,
        array $before,
        array $after,
        array $delta,
        string $motif,
        User $user
    ): void {
        PaymentReconciliationLog::create([
            'reconciliation_session_id' => $session->id,
            'paiement_id' => $paiement->id,
            'action_type' => $actionType,
            'snapshot_before' => $before,
            'snapshot_after' => $after,
            'delta' => $delta,
            'motif' => $motif,
            'performed_by' => $user->id,
            'performed_at' => now(),
        ]);
    }
}
