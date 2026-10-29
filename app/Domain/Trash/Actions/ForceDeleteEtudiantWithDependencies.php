<?php

namespace App\Domain\Trash\Actions;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Force-delete d'un étudiant soft-deleted avec cascade explicite sur ses
 * dépendances soft-deletées (inscriptions, paiements). Action exceptionnelle,
 * gardée par permission `students.force_delete_cascade` + motif texte
 * obligatoire (≥ 30 chars).
 *
 * Gardes :
 * 1. Paiements VALIDÉS encore actifs → REFUS systématique (intégrité OHADA,
 *    non bypassable ici).
 * 2. Notes liées à l'étudiant → REFUS par défaut, sauf si le caller fournit
 *    `bypassBlocking=true` ET que l'user possède la permission
 *    `students.force_delete_bypass_blocking`. Dans ce cas, les notes sont
 *    physiquement supprimées avant l'étudiant.
 *
 * Log warning permanent (motif + user + IDs + flag bypass).
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
     *   factures_cascade: int,
     *   bypass_blocking: bool,
     * }
     *
     * @throws \DomainException si motif < 30 chars, paiement validé actif,
     *                          notes présentes sans bypass, ou bypass sans permission.
     */
    public function execute(
        int $etudiantId,
        User $user,
        string $motif,
        bool $bypassBlocking = false
    ): array {
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

        // ─── Garde 1 OHADA : paiements VALIDÉS actifs (non bypassable) ───
        $paiementsValidesActifs = DB::table('esbtp_paiements')
            ->where('etudiant_id', $etudiantId)
            ->whereNull('deleted_at')
            ->whereIn('status', ['validé', 'valide', 'validated'])
            ->count();
        if ($paiementsValidesActifs > 0) {
            throw new \DomainException(
                "Suppression bloquée : {$paiementsValidesActifs} paiement(s) validé(s) actif(s) lié(s) à cet étudiant. "
                ."Ces paiements représentent des encaissements confirmés (intégrité OHADA — non contournable). "
                ."Annulez-les d'abord via le workflow comptable avant la suppression de l'étudiant."
            );
        }

        // ─── Garde 2 : notes liées — blocage doux bypassable ───
        $notesCount = Schema::hasTable('esbtp_notes')
            ? DB::table('esbtp_notes')->where('etudiant_id', $etudiantId)->count()
            : 0;

        if ($notesCount > 0 && ! $bypassBlocking) {
            throw new \DomainException(
                "Suppression bloquée : {$notesCount} note(s) liée(s) à cet étudiant. "
                ."Cochez l'option « Forcer malgré dépendances bloquantes » si vous avez la permission "
                ."`students.force_delete_bypass_blocking`."
            );
        }
        if ($bypassBlocking && ! $user->can('students.force_delete_bypass_blocking')) {
            throw new \DomainException(
                "Permission manquante : `students.force_delete_bypass_blocking` est requise pour "
                ."forcer la suppression définitive d'un étudiant ayant des dépendances bloquantes (notes)."
            );
        }

        // Compteurs pour audit / récap
        $inscriptionsDeleted = 0;
        $paiementsDeleted = 0;
        $notesCascade = $notesCount;  // ce qui sera physiquement supprimé (si bypass)
        $absencesCascade = 0;
        $fraisSubsCascade = 0;
        $facturesCascade = 0;

        DB::transaction(function () use (
            $etudiant, $etudiantId, $user, $motifTrim, $label, $bypassBlocking, $notesCount,
            &$inscriptionsDeleted, &$paiementsDeleted,
            &$absencesCascade, &$fraisSubsCascade, &$facturesCascade
        ) {
            // 1. Compte les cascades DB-level (informatif pour log)
            if (Schema::hasTable('esbtp_attendances')) {
                $absencesCascade = DB::table('esbtp_attendances')->where('etudiant_id', $etudiantId)->count();
            }

            // 2. Si bypass activé et notes présentes → suppression physique
            //    explicite des notes (sinon FK RESTRICT pourrait bloquer)
            if ($bypassBlocking && $notesCount > 0) {
                DB::table('esbtp_notes')->where('etudiant_id', $etudiantId)->delete();
            }

            // 2.5. Force-delete toutes les factures liées à l'étudiant (trashed + actives).
            //      esbtp_factures a deux FK RESTRICT : inscription_id et etudiant_id.
            //      Sans cette suppression, forceDelete() sur inscription ou sur l'étudiant
            //      serait bloqué par l'une ou l'autre contrainte.
            //      Les esbtp_facture_details cascadent au niveau DB (ON DELETE CASCADE).
            if (Schema::hasTable('esbtp_factures')) {
                $facturesCascade = DB::table('esbtp_factures')
                    ->where('etudiant_id', $etudiantId)
                    ->delete();
            }

            // 3. Force-delete inscriptions soft-deletées en premier (avec cascade FK)
            $inscriptionsTrashed = ESBTPInscription::onlyTrashed()
                ->where('etudiant_id', $etudiantId)
                ->get();
            foreach ($inscriptionsTrashed as $inscription) {
                if (Schema::hasTable('esbtp_frais_subscriptions')) {
                    $fraisSubsCascade += DB::table('esbtp_frais_subscriptions')
                        ->where('inscription_id', $inscription->id)
                        ->count();
                }
                $inscription->forceDelete();
                $inscriptionsDeleted++;
            }

            // 4. Force-delete inscriptions actives qui restent (rare — déjà
            //    soft-deletées par le booted::deleting de l'étudiant)
            ESBTPInscription::where('etudiant_id', $etudiantId)
                ->get()
                ->each(function ($inscription) use (&$inscriptionsDeleted, &$fraisSubsCascade) {
                    if (Schema::hasTable('esbtp_frais_subscriptions')) {
                        $fraisSubsCascade += DB::table('esbtp_frais_subscriptions')
                            ->where('inscription_id', $inscription->id)
                            ->count();
                    }
                    $inscription->forceDelete();
                    $inscriptionsDeleted++;
                });

            // 5. Force-delete paiements liés directement (etudiant_id direct,
            //    sans inscription) — non-validés uniquement (garde 1 OHADA ci-dessus)
            $paiementsResiduels = ESBTPPaiement::withTrashed()
                ->where('etudiant_id', $etudiantId)
                ->get();
            foreach ($paiementsResiduels as $paiement) {
                $paiement->forceDelete();
                $paiementsDeleted++;
            }

            // 6. Force-delete étudiant — déclenche cascade DB pour absences/etc.
            $etudiant->forceDelete();

            // 7. Log permanent OHADA-compliant
            Log::warning('FORCE_DELETE_CASCADE_ETUDIANT', [
                'etudiant_id' => $etudiantId,
                'etudiant_label' => $label,
                'performed_by_user_id' => $user->id,
                'performed_by_name' => $user->name,
                'motif' => $motifTrim,
                'bypass_blocking' => $bypassBlocking,
                'inscriptions_force_deleted' => $inscriptionsDeleted,
                'paiements_force_deleted' => $paiementsDeleted,
                'factures_force_deleted' => $facturesCascade,
                'notes_physically_deleted' => $bypassBlocking ? $notesCount : 0,
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
            'notes_cascade' => $bypassBlocking ? $notesCascade : 0,
            'absences_cascade' => $absencesCascade,
            'frais_subscriptions_cascade' => $fraisSubsCascade,
            'factures_cascade' => $facturesCascade,
            'bypass_blocking' => $bypassBlocking,
        ];
    }
}
