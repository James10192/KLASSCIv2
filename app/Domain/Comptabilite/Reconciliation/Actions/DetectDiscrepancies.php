<?php

namespace App\Domain\Comptabilite\Reconciliation\Actions;

use App\Domain\Comptabilite\Reconciliation\Models\CashCount;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationDiscrepancy;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Scanne les cash_counts d'une session et crée 1 discrepancy par écart != 0.
 *
 * Idempotente :
 * - Si une discrepancy existe déjà pour un (session, cash_count) avec action='a_traiter'
 *   ou 'en_revue', on met juste à jour le montant. On ne touche pas aux discrepancies
 *   déjà résolues ou rejetées (audit trail préservé).
 * - Si un cash_count repasse à écart=0 entre 2 runs, la discrepancy 'a_traiter'
 *   correspondante est supprimée.
 *
 * Type inféré : ecart>0 → paiement_manquant, ecart<0 → paiement_en_trop.
 */
class DetectDiscrepancies
{
    public function execute(ReconciliationSession $session): Collection
    {
        if (!$session->isModifiable()) {
            throw new \DomainException("Session {$session->code} non modifiable.");
        }

        // Setting tenant : seuil tolérance écart (default 100 FCFA pour absorber arrondis)
        $tolerance = (float) SettingsHelper::get('comptabilite.reconciliation.ecart_tolerance', 100);

        return DB::transaction(function () use ($session, $tolerance) {
            $session->loadMissing('cashCounts');
            $created = collect();

            foreach ($session->cashCounts as $cc) {
                $ecart = (float) $cc->ecart;
                $existing = ReconciliationDiscrepancy::where('reconciliation_session_id', $session->id)
                    ->where('cash_count_id', $cc->id)
                    ->whereIn('action', ['a_traiter', 'en_revue'])
                    ->first();

                if (abs($ecart) <= $tolerance) {
                    // Écart dans la tolérance : delete la discrepancy 'a_traiter' si elle existait
                    if ($existing) {
                        $existing->delete();
                    }
                    continue;
                }

                $type = $ecart > 0 ? 'paiement_manquant' : 'paiement_en_trop';
                $autoMotif = "Écart auto-détecté pour le mode {$cc->modeLabel()} "
                    . "(physique " . number_format((float) $cc->montant_compte, 0, ',', ' ')
                    . " vs système " . number_format((float) $cc->montant_systeme, 0, ',', ' ')
                    . " = " . number_format($ecart, 0, ',', ' ') . " FCFA)";

                if ($existing) {
                    $existing->update([
                        'type' => $type,
                        'montant_ecart' => $ecart,
                        'motif' => $autoMotif,
                    ]);
                    $created->push($existing->fresh());
                } else {
                    $created->push(ReconciliationDiscrepancy::create([
                        'reconciliation_session_id' => $session->id,
                        'cash_count_id' => $cc->id,
                        'type' => $type,
                        'montant_ecart' => $ecart,
                        'action' => 'a_traiter',
                        'motif' => $autoMotif,
                    ]));
                }
            }

            return $created;
        });
    }
}
