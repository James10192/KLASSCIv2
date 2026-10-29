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
     * Soumettre ou re-soumettre une justification.
     *
     * Une absence se justifie quand aucune justification n'a encore été
     * déposée (statut null) ou quand la précédente a été rejetée. Elle ne se
     * re-soumet pas tant qu'elle est en attente : cela réinitialiserait la
     * file de l'administration. Une justification approuvée est terminale,
     * et une absence excusée à l'ancienne n'a rien à justifier.
     *
     * Étudiant : sa propre absence, avec la permission attendances.justify_own.
     * Admin : toute absence, avec la permission attendances.justify_process
     * (cas exceptionnel : saisie par le secrétariat).
     */
    public function submit(User $user, ESBTPAttendance $absence): bool
    {
        if (!$this->peutEtreSoumise($absence)) {
            return false;
        }

        if ($user->can('attendances.justify_process')) {
            return true;
        }

        if (!$user->can('attendances.justify_own')) {
            return false;
        }

        $etudiant = $user->etudiant ?? null;

        return $etudiant && (int) $etudiant->id === (int) $absence->etudiant_id;
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

    /**
     * L'état de l'absence permet-il un dépôt ? Indépendant de la personne.
     */
    private function peutEtreSoumise(ESBTPAttendance $absence): bool
    {
        if ($absence->statut === 'excuse') {
            return false;
        }

        $status = $absence->justification_status;

        return $status === null || $status->isEditableByStudent();
    }
}
