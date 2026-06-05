<?php

namespace App\Domain\Trash\Actions;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Force-delete d'un étudiant soft-deleted avec cascade explicite sur ses
 * dépendances soft-deletées (inscriptions, paiements). Action exceptionnelle,
 * gardée par permission `students.force_delete_cascade` + motif texte
 * obligatoire (≥ 30 chars).
 *
 * Pourquoi ce service séparé du forceDelete simple :
 * - Bloque les paiements VALIDÉS non soft-deletés (intégrité OHADA)
 * - Force-delete d'abord les enfants soft-deletés (inscriptions, paiements)
 *   pour éviter les contraintes FK RESTRICT résiduelles
 * - Logs warning permanent avec motif + user + IDs supprimés
 * - Retourne un récap chiffré pour audit trail
 *
 * Garde de sécurité comptable :
 * Si l'étudiant a au moins 1 paiement VALIDÉ encore actif (non soft-deleted),
 * on REFUSE l'action — ces paiements représentent des encaissements OHADA
 * confirmés qui doivent passer par un workflow d'annulation comptable (pas
 * une suppression d'étudiant).
 */
class ForceDeleteEtudiantWithDependencies
{
    /**
     * @return array{
     *   etudiant_id: int,
     *   etudiant_label: string,
     *   inscriptions_deleted: int,
     *   paiements_deleted: int,
     *   notes_cascade: int,
     *   absences_cascade: int,
     *   frais_subscriptions_cascade: int,
     * }
     *
     * @throws \DomainException si motif < 30 chars ou paiement validé actif
     */
    public function execute(int $etudiantId, User $user, string $motif): array
    {
        $motifTrim = trim($motif);
        if (mb_strlen($motifTrim) < 30) {
            throw new \DomainException(
                'Motif obligatoire de 30 caractères minimum pour la suppression cascade ('
                .mb_strlen($motifTrim).' fournis).'
            );
        }

        $etudiant = ESBTPEtudiant::onlyTrashed()->findOrFail($etudiantId);
        $label = trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? ''));
        if ($etudiant->matricule) {
            $label .= ' ('.$etudiant->matricule.')';
        }

        // ⚠️ Garde OHADA : refuser si paiement VALIDÉ encore actif (non soft-deleted)
        $paiementsValidesActifs = DB::table('esbtp_paiements')
            ->where('etudiant_id', $etudiantId)
            ->whereNull('deleted_at')
            ->whereIn('status', ['validé', 'valide', 'validated'])
            ->count();
        if ($paiementsValidesActifs > 0) {
            throw new \DomainException(
                "Suppression bloquée : {$paiementsValidesActifs} paiement(s) validé(s) actif(s) lié(s) à cet étudiant. "
                ."Ces paiements représentent des encaissements confirmés (intégrité OHADA). "
                ."Annulez-les d'abord via le workflow comptable avant la suppression de l'étudiant."
            );
        }

        // Compteurs pour audit / récap
        $inscriptionsDeleted = 0;
        $paiementsDeleted = 0;
        $notesCascade = 0;
        $absencesCascade = 0;
        $fraisSubsCascade = 0;

        DB::transaction(function () use (
            $etudiant, $etudiantId, $user, $motifTrim, $label,
            &$inscriptionsDeleted, &$paiementsDeleted,
            &$notesCascade, &$absencesCascade, &$fraisSubsCascade
        ) {
            // 1. Compte les cascades DB-level (informatif)
            if (\Illuminate\Support\Facades\Schema::hasTable('esbtp_notes')) {
                $notesCascade = DB::table('esbtp_notes')->where('etudiant_id', $etudiantId)->count();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('esbtp_attendances')) {
                $absencesCascade = DB::table('esbtp_attendances')->where('etudiant_id', $etudiantId)->count();
            }

            // 2. Force-delete inscriptions soft-deletées en premier (avec cascade FK)
            $inscriptionsTrashed = ESBTPInscription::onlyTrashed()
                ->where('etudiant_id', $etudiantId)
                ->get();
            foreach ($inscriptionsTrashed as $inscription) {
                // Compter les frais_subscriptions liés AVANT delete
                if (\Illuminate\Support\Facades\Schema::hasTable('esbtp_frais_subscriptions')) {
                    $fraisSubsCascade += DB::table('esbtp_frais_subscriptions')
                        ->where('inscription_id', $inscription->id)
                        ->count();
                }
                $inscription->forceDelete();
                $inscriptionsDeleted++;
            }

            // 3. Force-delete inscriptions actives qui restent (rare — devrait être 0
            //    après le booted::deleting de l'étudiant qui les a déjà soft-deletées)
            ESBTPInscription::where('etudiant_id', $etudiantId)
                ->get()
                ->each(function ($inscription) use (&$inscriptionsDeleted, &$fraisSubsCascade) {
                    if (\Illuminate\Support\Facades\Schema::hasTable('esbtp_frais_subscriptions')) {
                        $fraisSubsCascade += DB::table('esbtp_frais_subscriptions')
                            ->where('inscription_id', $inscription->id)
                            ->count();
                    }
                    $inscription->forceDelete();
                    $inscriptionsDeleted++;
                });

            // 4. Force-delete paiements liés directement (etudiant_id direct, sans inscription)
            $paiementsResiduels = ESBTPPaiement::withTrashed()
                ->where('etudiant_id', $etudiantId)
                ->get();
            foreach ($paiementsResiduels as $paiement) {
                $paiement->forceDelete();
                $paiementsDeleted++;
            }

            // 5. Force-delete étudiant — déclenche cascade DB pour notes/absences/etc.
            $etudiant->forceDelete();

            // 6. Log permanent OHADA-compliant
            Log::warning('FORCE_DELETE_CASCADE_ETUDIANT', [
                'etudiant_id' => $etudiantId,
                'etudiant_label' => $label,
                'performed_by_user_id' => $user->id,
                'performed_by_name' => $user->name,
                'motif' => $motifTrim,
                'inscriptions_force_deleted' => $inscriptionsDeleted,
                'paiements_force_deleted' => $paiementsDeleted,
                'notes_cascade_db' => $notesCascade,
                'absences_cascade_db' => $absencesCascade,
                'frais_subscriptions_cascade_db' => $fraisSubsCascade,
                'timestamp' => now()->toIso8601String(),
            ]);
        });

        return [
            'etudiant_id' => $etudiantId,
            'etudiant_label' => $label,
            'inscriptions_deleted' => $inscriptionsDeleted,
            'paiements_deleted' => $paiementsDeleted,
            'notes_cascade' => $notesCascade,
            'absences_cascade' => $absencesCascade,
            'frais_subscriptions_cascade' => $fraisSubsCascade,
        ];
    }
}
