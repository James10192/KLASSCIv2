<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ESBTPPaiement;
use Illuminate\Support\Facades\Log;

/**
 * Les deux verrous qui protegent un versement deja passe en comptabilite.
 *
 * Ils vivaient en methodes privees de {@see \App\Http\Controllers\ESBTPPaiementController}.
 * Extraits ici parce qu'un SECOND ecran ecrit desormais sur de l'argent
 * encaisse : la correction d'imputation
 * ({@see \App\Http\Controllers\Comptabilite\VentilationPaiementController}).
 *
 * Les recopier la-bas aurait fabrique deux verrous qui derivent — le jour ou
 * l'un gagne une exception, une porte reste ouverte sans que personne ne s'en
 * apercoive. Un verrou en double n'est plus un verrou.
 */
trait VerrouilleLesPeriodesComptables
{
    /**
     * S1.4 — Garde anti-modification retroactive (verrouillage de periode comptable).
     *
     * Une fois qu'un mois est cloture (setting `comptabilite.period_locked_until`),
     * plus aucun paiement anterieur a cette date ne peut etre modifie, supprime ou rejete.
     * Garantit la tracabilite comptable et la conformite OHADA.
     *
     * Bypass possible via permission `comptabilite.period.bypass_lock` (rare,
     * pour corrections exceptionnelles). superAdmin/serviceTechnique passent via Gate::before.
     *
     * @return array{message: string}|null Null si OK, ou ['message' => '...'] si bloque.
     */
    protected function assertPeriodNotLocked(ESBTPPaiement $paiement): ?array
    {
        $lockedUntil = \App\Helpers\SettingsHelper::get('comptabilite.period_locked_until');
        if (empty($lockedUntil)) {
            return null;
        }

        try {
            $lockDate = \Carbon\Carbon::parse($lockedUntil)->endOfDay();
        } catch (\Throwable $e) {
            return null; // Setting mal formaté, on n'applique pas de garde
        }

        // Date de référence du paiement : date_paiement (la vraie date métier) ou created_at fallback
        $paiementDate = $paiement->date_paiement
            ? \Carbon\Carbon::parse($paiement->date_paiement)
            : ($paiement->created_at ?: now());

        if ($paiementDate->gt($lockDate)) {
            return null; // Postérieur au verrouillage → modifiable
        }

        // Bypass autorisé (superAdmin via Gate::before *, ou perm explicite)
        if (auth()->user()?->can('comptabilite.period.bypass_lock')) {
            Log::warning('[S1.4] Bypass verrouillage période utilisé', [
                'paiement_id' => $paiement->id,
                'paiement_date' => $paiementDate->toDateString(),
                'period_locked_until' => $lockDate->toDateString(),
                'user_id' => auth()->id(),
            ]);

            return null;
        }

        return [
            'message' => sprintf(
                'Action refusée : le paiement du %s appartient à une période comptable verrouillée (jusqu\'au %s). Demandez à un comptable habilité de débloquer la période ou créez une écriture corrective sur la période courante.',
                $paiementDate->translatedFormat('d/m/Y'),
                $lockDate->translatedFormat('d/m/Y'),
            ),
        ];
    }

    /**
     * PR2 réconciliation — vérifie qu'un paiement n'est pas verrouillé par une
     * session de réconciliation clôturée. Bypass possible via permission
     * `comptabilite.reconciliation.bypass_lock` (superAdmin/serviceTechnique
     * passent via Gate::before).
     *
     * @return array{message: string}|null Null si OK, ou ['message' => '...'] si bloque.
     */
    protected function assertReconciliationNotLocked(ESBTPPaiement $paiement): ?array
    {
        if (! $paiement->reconciliation_locked_at) {
            return null;
        }

        if (auth()->user()?->can('comptabilite.reconciliation.bypass_lock')) {
            Log::warning('Réconciliation : bypass verrouillage paiement utilisé', [
                'paiement_id' => $paiement->id,
                'reconciliation_locked_at' => $paiement->reconciliation_locked_at?->toIso8601String(),
                'session_id' => $paiement->last_reconciliation_session_id,
                'user_id' => auth()->id(),
            ]);

            return null;
        }

        return [
            'message' => sprintf(
                'Action refusée : ce paiement a été verrouillé par la réconciliation %s. '
                .'Demandez à un superAdmin de rouvrir la session ou créez une écriture corrective.',
                $paiement->last_reconciliation_session_id
                    ? "(session #{$paiement->last_reconciliation_session_id})"
                    : ''
            ),
        ];
    }
}
