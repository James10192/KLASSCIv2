<?php

namespace App\Services\Trash;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Analyse les dépendances bloquantes ou cascadantes pour les actions
 * "Restaurer" et "Supprimer définitivement" dans la corbeille KLASSCI.
 *
 * Quand l'utilisateur clique sur Restaurer / Supprimer définitivement, on lui
 * affiche AVANT l'action :
 *  - blocking_restore : ce qui empêche la restauration (ex: doublon métier)
 *  - cascading_restore : ce qui sera restauré en cascade (ex: inscriptions liées)
 *  - blocking_force_delete : ce qui empêche la suppression définitive
 *    chaque entry porte un flag `bypassable` :
 *      - false → blocage dur (paiements validés OHADA) — JAMAIS contournable ici
 *      - true → blocage doux (notes) — contournable avec
 *               permission `students.force_delete_bypass_blocking`
 *  - cascading_force_delete : ce qui sera supprimé en cascade (FK ON DELETE CASCADE
 *    ou inscriptions/paiements de la corbeille)
 *
 * Convention Marcel (5 juin 2026) : les notes RESTENT bloquantes par défaut.
 * On ajoute une permission de bypass séparée pour les superAdmin qui acceptent
 * de violer l'intégrité notes. Les paiements validés actifs sont strictement
 * non-bypassables (intégrité comptable OHADA).
 *
 * Utilisé par les 3 trash controllers via une nouvelle route :
 *   GET /esbtp/trash/{type}/{id}/dependencies
 */
class TrashDependencyAnalyzer
{
    /**
     * Analyse complète pour un étudiant soft-deleted.
     */
    public function forEtudiant(int $id): array
    {
        $etudiant = ESBTPEtudiant::onlyTrashed()->findOrFail($id);

        $label = trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? ''));
        if ($etudiant->matricule) {
            $label .= ' ('.$etudiant->matricule.')';
        }

        // ── Cascading restore : compte des entités soft-deletées qui seront
        // ressuscitées par le restore (toutes inscriptions/paiements soft-del
        // attachés, pas seulement la fenêtre ±2 min — UX intuitive Marcel).
        $inscriptionsTrashedCount = DB::table('esbtp_inscriptions')
            ->where('etudiant_id', $id)
            ->whereNotNull('deleted_at')
            ->count();
        $paiementsTrashedCount = DB::table('esbtp_paiements')
            ->where('etudiant_id', $id)
            ->whereNotNull('deleted_at')
            ->count();

        $cascadingRestore = [];
        if ($inscriptionsTrashedCount > 0) {
            $cascadingRestore[] = [
                'type' => 'inscriptions_trashed',
                'count' => $inscriptionsTrashedCount,
                'label' => $inscriptionsTrashedCount.' inscription(s) seront restaurée(s) en cascade',
                'icon' => 'fa-file-signature',
            ];
        }
        if ($paiementsTrashedCount > 0) {
            $cascadingRestore[] = [
                'type' => 'paiements_trashed',
                'count' => $paiementsTrashedCount,
                'label' => $paiementsTrashedCount.' paiement(s) seront restauré(s) en cascade',
                'icon' => 'fa-money-bill-wave',
            ];
        }

        // ── Décompte des entités actives + dépendances dénormalisées ──
        $inscriptionsActivesCount = DB::table('esbtp_inscriptions')
            ->where('etudiant_id', $id)
            ->whereNull('deleted_at')
            ->count();
        $paiementsActifsNonValidesCount = DB::table('esbtp_paiements')
            ->where('etudiant_id', $id)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['validé', 'valide', 'validated'])
            ->count();
        $notesCount = Schema::hasTable('esbtp_notes')
            ? DB::table('esbtp_notes')->where('etudiant_id', $id)->count()
            : 0;
        $absencesCount = 0;
        if (Schema::hasTable('esbtp_attendances')) {
            $absencesCount = DB::table('esbtp_attendances')->where('etudiant_id', $id)->count();
        } elseif (Schema::hasTable('esbtp_absences')) {
            $absencesCount = DB::table('esbtp_absences')->where('etudiant_id', $id)->count();
        }

        // ── Blocking force-delete : 2 catégories ──
        $blockingForceDelete = [];

        // (1) Paiements VALIDÉS actifs = blocage DUR OHADA (jamais bypassable
        //     via cette UI — passer par workflow d'annulation comptable).
        $paiementsValidesActifs = DB::table('esbtp_paiements')
            ->where('etudiant_id', $id)
            ->whereNull('deleted_at')
            ->whereIn('status', ['validé', 'valide', 'validated'])
            ->count();
        if ($paiementsValidesActifs > 0) {
            $blockingForceDelete[] = [
                'type' => 'paiements_valides',
                'count' => $paiementsValidesActifs,
                'label' => $paiementsValidesActifs.' paiement(s) validé(s) actif(s) — intégrité comptable OHADA',
                'icon' => 'fa-shield',
                'bypassable' => false,
            ];
        }

        // (2) Notes liées = blocage DOUX (bypassable avec permission
        //     `students.force_delete_bypass_blocking`).
        if ($notesCount > 0) {
            $blockingForceDelete[] = [
                'type' => 'notes',
                'count' => $notesCount,
                'label' => $notesCount.' note(s) liée(s) à l\'étudiant',
                'icon' => 'fa-graduation-cap',
                'bypassable' => true,
            ];
        }

        // ── Cascading force-delete : entités déjà en corbeille + cascade FK DB
        // (présences). Ces entités seront supprimées automatiquement par le
        // force-delete physique, sans blocage.
        $cascadingForceDelete = [];
        if ($inscriptionsTrashedCount > 0) {
            $cascadingForceDelete[] = [
                'type' => 'inscriptions_trashed',
                'count' => $inscriptionsTrashedCount,
                'label' => $inscriptionsTrashedCount.' inscription(s) déjà dans la corbeille',
                'icon' => 'fa-trash',
            ];
        }
        if ($paiementsTrashedCount > 0) {
            $cascadingForceDelete[] = [
                'type' => 'paiements_trashed',
                'count' => $paiementsTrashedCount,
                'label' => $paiementsTrashedCount.' paiement(s) déjà dans la corbeille',
                'icon' => 'fa-trash',
            ];
        }
        if ($paiementsActifsNonValidesCount > 0) {
            $cascadingForceDelete[] = [
                'type' => 'paiements_non_valides',
                'count' => $paiementsActifsNonValidesCount,
                'label' => $paiementsActifsNonValidesCount.' paiement(s) actif(s) non validé(s) (cascade FK)',
                'icon' => 'fa-money-bill-wave',
            ];
        }
        if ($absencesCount > 0) {
            $cascadingForceDelete[] = [
                'type' => 'absences',
                'count' => $absencesCount,
                'label' => $absencesCount.' enregistrement(s) de présence/absence (cascade FK)',
                'icon' => 'fa-calendar-check',
            ];
        }

        $hasBypassableBlocking = (bool) collect($blockingForceDelete)
            ->first(fn ($b) => is_array($b) && ($b['bypassable'] ?? false));
        $hasHardBlocking = (bool) collect($blockingForceDelete)
            ->first(fn ($b) => is_array($b) && ! ($b['bypassable'] ?? false));

        return [
            'entity_type' => 'etudiants',
            'entity_id' => $id,
            'entity_label' => $label !== '' ? $label : 'Étudiant #'.$id,
            'deleted_at' => optional($etudiant->deleted_at)->toIso8601String(),
            'blocking_restore' => [],
            'cascading_restore' => $cascadingRestore,
            'blocking_force_delete' => $blockingForceDelete,
            'cascading_force_delete' => $cascadingForceDelete,
            'has_blocking' => count($blockingForceDelete) > 0,
            'has_bypassable_blocking' => $hasBypassableBlocking,
            'has_hard_blocking' => $hasHardBlocking,
            'cascade_counts' => [
                'inscriptions_trashed' => $inscriptionsTrashedCount,
                'inscriptions_actives' => $inscriptionsActivesCount,
                'paiements_trashed' => $paiementsTrashedCount,
                'paiements_actifs_non_valides' => $paiementsActifsNonValidesCount,
                'paiements_valides_bloquants' => $paiementsValidesActifs,
                'notes' => $notesCount,
                'absences' => $absencesCount,
            ],
        ];
    }

    /**
     * Analyse complète pour une inscription soft-deleted.
     */
    public function forInscription(int $id): array
    {
        $inscription = ESBTPInscription::onlyTrashed()->findOrFail($id);
        $classe = $inscription->classe_id
            ? \App\Models\ESBTPClasse::find($inscription->classe_id)
            : null;

        $etudiantId = $inscription->etudiant_id;
        $etudiantTrashed = $etudiantId
            ? ESBTPEtudiant::onlyTrashed()->whereKey($etudiantId)->exists()
            : false;
        $etudiant = $etudiantId
            ? ESBTPEtudiant::withTrashed()->find($etudiantId)
            : null;

        $label = 'Inscription #'.$id;
        if ($etudiant) {
            $label .= ' — '.trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? ''));
        }
        if ($classe) {
            $label .= ' / '.$classe->name;
        }

        $blockingRestore = [];
        $cascadingRestore = [];
        $blockingForceDelete = [];
        $cascadingForceDelete = [];

        // Si étudiant soft-deleted → cascade restaurer aussi
        if ($etudiantTrashed && $etudiant) {
            $cascadingRestore[] = [
                'type' => 'etudiant',
                'count' => 1,
                'label' => "L'étudiant ".trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')).' sera aussi restauré automatiquement',
                'icon' => 'fa-user-graduate',
            ];
        }

        // Paiements liés en corbeille → ne seront pas auto-restaurés mais à signaler
        $paiementsActifs = DB::table('esbtp_paiements')
            ->where('inscription_id', $id)
            ->whereNull('deleted_at')
            ->count();
        $paiementsTrashed = DB::table('esbtp_paiements')
            ->where('inscription_id', $id)
            ->whereNotNull('deleted_at')
            ->count();

        if ($paiementsActifs > 0) {
            $blockingForceDelete[] = [
                'type' => 'paiements',
                'count' => $paiementsActifs,
                'label' => $paiementsActifs.' paiement(s) actif(s) lié(s) à cette inscription',
                'icon' => 'fa-money-bill-wave',
                'bypassable' => false,
            ];
        }
        if ($paiementsTrashed > 0) {
            $cascadingForceDelete[] = [
                'type' => 'paiements_trashed',
                'count' => $paiementsTrashed,
                'label' => $paiementsTrashed.' paiement(s) déjà dans la corbeille (seront supprimés définitivement)',
                'icon' => 'fa-trash',
            ];
        }

        // Frais subscriptions liés
        if (Schema::hasTable('esbtp_frais_subscriptions')) {
            $fraisSubsCount = DB::table('esbtp_frais_subscriptions')
                ->where('inscription_id', $id)
                ->count();
            if ($fraisSubsCount > 0) {
                $cascadingForceDelete[] = [
                    'type' => 'frais_subscriptions',
                    'count' => $fraisSubsCount,
                    'label' => $fraisSubsCount.' souscription(s) de frais liée(s)',
                    'icon' => 'fa-receipt',
                ];
            }
        }

        return [
            'entity_type' => 'inscriptions',
            'entity_id' => $id,
            'entity_label' => $label,
            'deleted_at' => optional($inscription->deleted_at)->toIso8601String(),
            'blocking_restore' => $blockingRestore,
            'cascading_restore' => $cascadingRestore,
            'blocking_force_delete' => $blockingForceDelete,
            'cascading_force_delete' => $cascadingForceDelete,
            'has_blocking' => count($blockingForceDelete) > 0,
            'has_bypassable_blocking' => false,
            'has_hard_blocking' => count($blockingForceDelete) > 0,
        ];
    }

    /**
     * Analyse complète pour un paiement soft-deleted.
     */
    public function forPaiement(int $id): array
    {
        $paiement = ESBTPPaiement::onlyTrashed()->findOrFail($id);

        // Préférence : utiliser inscription_id du paiement (lien direct).
        // Fallback : etudiant_id direct sur le paiement.
        $inscriptionId = $paiement->inscription_id;
        $inscription = $inscriptionId
            ? ESBTPInscription::withTrashed()->find($inscriptionId)
            : null;
        $etudiantId = $inscription?->etudiant_id ?? $paiement->etudiant_id;
        $etudiant = $etudiantId
            ? ESBTPEtudiant::withTrashed()->find($etudiantId)
            : null;

        $inscriptionTrashed = $inscriptionId
            ? ESBTPInscription::onlyTrashed()->whereKey($inscriptionId)->exists()
            : false;
        $etudiantTrashed = $etudiantId
            ? ESBTPEtudiant::onlyTrashed()->whereKey($etudiantId)->exists()
            : false;

        $label = 'Paiement #'.$id;
        if ($paiement->reference) {
            $label .= ' ('.$paiement->reference.')';
        }
        $label .= ' — '.number_format((float) ($paiement->montant ?? 0), 0, ',', ' ').' FCFA';
        if ($etudiant) {
            $label .= ' / '.trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? ''));
        }

        $blockingRestore = [];
        $cascadingRestore = [];
        $blockingForceDelete = [];
        $cascadingForceDelete = [];

        if ($etudiantTrashed && $etudiant) {
            $cascadingRestore[] = [
                'type' => 'etudiant',
                'count' => 1,
                'label' => "L'étudiant ".trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')).' sera aussi restauré automatiquement',
                'icon' => 'fa-user-graduate',
            ];
        }
        if ($inscriptionTrashed && $inscription) {
            $cascadingRestore[] = [
                'type' => 'inscription',
                'count' => 1,
                'label' => "L'inscription associée sera aussi restaurée automatiquement",
                'icon' => 'fa-file-signature',
            ];
        }

        // Pour un paiement validé, attention : règle métier OHADA — bloquer la
        // suppression définitive d'un paiement validé qui a été soft-deleted ?
        // Pour l'instant on signale juste comme warning.
        if (in_array($paiement->status ?? null, ['validé', 'valide', 'validated'], true)) {
            $cascadingForceDelete[] = [
                'type' => 'paiement_valide',
                'count' => 1,
                'label' => 'Ce paiement avait été validé — la suppression définitive est irréversible (impact comptable)',
                'icon' => 'fa-exclamation-triangle',
                'severity' => 'warning',
            ];
        }

        return [
            'entity_type' => 'paiements',
            'entity_id' => $id,
            'entity_label' => $label,
            'deleted_at' => optional($paiement->deleted_at)->toIso8601String(),
            'blocking_restore' => $blockingRestore,
            'cascading_restore' => $cascadingRestore,
            'blocking_force_delete' => $blockingForceDelete,
            'cascading_force_delete' => $cascadingForceDelete,
            'has_blocking' => count($blockingForceDelete) > 0,
            'has_bypassable_blocking' => false,
            'has_hard_blocking' => count($blockingForceDelete) > 0,
        ];
    }
}
