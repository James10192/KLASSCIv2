<?php

namespace App\Policies;

use App\Enums\JustificationStatus;
use App\Models\ESBTPAttendance;
use App\Models\User;

/**
 * Policy pour les actions sur la justification d'une absence.
 *
 * Cible la même entité que ESBTPAttendance — mais centralise UNIQUEMENT
 * la logique de justification (workflow + document privé), pas le CRUD
 * général d'attendance.
 *
 * Gate::before couvre superAdmin automatiquement (rule permissions).
 */
class AbsenceJustificationPolicy
{
    /**
     * Étudiant : peut soumettre/re-soumettre sa propre absence si pas déjà APPROVED.
     * Admin : peut aussi soumettre (ex: cas exceptionnel saisie par secrétaire).
     */
    public function submit(User $user, ESBTPAttendance $absence): bool
    {
        // Admin avec perm process peut soumettre pour l'étudiant
        if ($user->can('attendances.justify_process')) {
            return $absence->justification_status !== JustificationStatus::APPROVED;
        }

        // Étudiant propriétaire avec perm view_own (= sa fiche)
        if (!$user->can('attendances.justify_own')) {
            return false;
        }

        $etudiant = $user->etudiant ?? null;
        if (!$etudiant || (int) $etudiant->id !== (int) $absence->etudiant_id) {
            return false;
        }

        // Pas re-soumissible si déjà approuvée
        return $absence->justification_status !== JustificationStatus::APPROVED;
    }

    /**
     * Traiter (approve/reject) une justification : admin uniquement.
     * Seules les justifications PENDING sont actionnables (re-traiter une rejected
     * = soumission étudiant d'abord).
     */
    public function process(User $user, ESBTPAttendance $absence): bool
    {
        if (!$user->can('attendances.justify_process')) {
            return false;
        }
        return $absence->justification_status === JustificationStatus::PENDING;
    }

    /**
     * Visualiser le document de justification.
     *
     * Autorisé pour :
     *   - L'étudiant propriétaire (consultation de son propre justificatif)
     *   - L'admin avec permission de traitement
     */
    public function viewDocument(User $user, ESBTPAttendance $absence): bool
    {
        // Admin processing
        if ($user->can('attendances.justify_process')) {
            return true;
        }

        // Étudiant propriétaire
        if (!$user->can('attendances.justify_own')) {
            return false;
        }

        $etudiant = $user->etudiant ?? null;
        return $etudiant && (int) $etudiant->id === (int) $absence->etudiant_id;
    }
}
