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
 *  - cascading_restore : ce qui sera restauré en cascade (ex: étudiant parent)
 *  - blocking_force_delete : ce qui empêche la suppression définitive
 *  - cascading_force_delete : ce qui sera supprimé en cascade (FK ON DELETE CASCADE
 *    ou supprimé manuellement par hook deleting())
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

        // Pour restaurer : pas de blocking automatique (l'étudiant est isolé).
        // Cascade restaurée : les inscriptions/paiements liés NE sont PAS auto-restaurés
        // car ils peuvent être indépendamment vivants ou supprimés. On informe juste.
        $blockingRestore = [];
        $cascadingRestore = [];

        // Pour la suppression définitive : compter les enfants en DB.
        // FK contraintes peuvent empêcher le forceDelete si pas de ON DELETE CASCADE.
        $cascadingForceDelete = [];
        $blockingForceDelete = [];

        // Compter directement via la DB (incluant soft-deleted) car withTrashed()
        // sur une relation ne fonctionne pas avec onlyTrashed().
        $inscriptionsCount = DB::table('esbtp_inscriptions')
            ->where('etudiant_id', $id)
            ->whereNull('deleted_at')
            ->count();
        $inscriptionsTrashedCount = DB::table('esbtp_inscriptions')
            ->where('etudiant_id', $id)
            ->whereNotNull('deleted_at')
            ->count();
        $paiementsCount = DB::table('esbtp_paiements')
            ->where('etudiant_id', $id)
            ->whereNull('deleted_at')
            ->count();
        $paiementsTrashedCount = DB::table('esbtp_paiements')
            ->where('etudiant_id', $id)
            ->whereNotNull('deleted_at')
            ->count();
        $notesCount = Schema::hasTable('esbtp_notes')
            ? DB::table('esbtp_notes')->where('etudiant_id', $id)->count()
            : 0;
        // Table absences/présences : nom canonique = esbtp_attendances
        // (le pré-existant 'esbtp_absences' peut exister sur certains tenants pour legacy)
        $absencesCount = 0;
        if (Schema::hasTable('esbtp_attendances')) {
            $absencesCount = DB::table('esbtp_attendances')->where('etudiant_id', $id)->count();
        } elseif (Schema::hasTable('esbtp_absences')) {
            $absencesCount = DB::table('esbtp_absences')->where('etudiant_id', $id)->count();
        }

        // ⚠️ Garde OHADA stricte : paiements VALIDÉS actifs = truly blocking
        // (encaissements confirmés — doivent passer par workflow annulation comptable).
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
            ];
        }

        // Cascade info (suppression cascade DB-level ou via Action dédiée).
        // Toutes ces entités peuvent être supprimées via `students.force_delete_cascade`
        // (avec motif obligatoire) car FK constraints ont onDelete('cascade')
        // OU sont gérées par l'Action ForceDeleteEtudiantWithDependencies.
        if ($inscriptionsCount > 0) {
            $cascadingForceDelete[] = [
                'type' => 'inscriptions',
                'count' => $inscriptionsCount,
                'label' => $inscriptionsCount.' inscription(s) active(s) (cascade FK)',
                'icon' => 'fa-file-signature',
                'severity' => 'high',
            ];
        }
        if ($inscriptionsTrashedCount > 0) {
            $cascadingForceDelete[] = [
                'type' => 'inscriptions_trashed',
                'count' => $inscriptionsTrashedCount,
                'label' => $inscriptionsTrashedCount.' inscription(s) déjà dans la corbeille',
                'icon' => 'fa-trash',
            ];
        }
        if ($paiementsCount > 0) {
            $cascadingForceDelete[] = [
                'type' => 'paiements',
                'count' => $paiementsCount,
                'label' => $paiementsCount.' paiement(s) actif(s) non validé(s) (cascade FK)',
                'icon' => 'fa-money-bill-wave',
                'severity' => 'high',
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
        if ($notesCount > 0) {
            $cascadingForceDelete[] = [
                'type' => 'notes',
                'count' => $notesCount,
                'label' => $notesCount.' note(s) (cascade FK)',
                'icon' => 'fa-graduation-cap',
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

        // Flag spécial : indique si une cascade dense (entités actives) est nécessaire
        // → l'UI affichera l'option "Forcer suppression cascade" (rouge, motif obligatoire)
        $requiresCascade = ($inscriptionsCount + $paiementsCount) > 0;

        return [
            'entity_type' => 'etudiants',
            'entity_id' => $id,
            'entity_label' => $label !== '' ? $label : 'Étudiant #'.$id,
            'deleted_at' => optional($etudiant->deleted_at)->toIso8601String(),
            'blocking_restore' => $blockingRestore,
            'cascading_restore' => $cascadingRestore,
            'blocking_force_delete' => $blockingForceDelete,
            'cascading_force_delete' => $cascadingForceDelete,
            'has_blocking' => count($blockingForceDelete) > 0,
            'requires_cascade' => $requiresCascade,
            'cascade_counts' => [
                'inscriptions_actives' => $inscriptionsCount,
                'paiements_actifs_non_valides' => $paiementsCount,
                'paiements_valides_bloquants' => $paiementsValidesActifs,
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
        ];
    }
}
