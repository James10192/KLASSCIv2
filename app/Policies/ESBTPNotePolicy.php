<?php

namespace App\Policies;

use App\Models\ESBTPNote;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ESBTPNotePolicy
{
    use HandlesAuthorization;


    public function viewAny(User $user)
    {
        return $user->can('module.notes_evaluations.access');
    }

    /**
     * Enseignant: uniquement les notes des évaluations qu'il a créées (enseignant_id).
     * Étudiant: uniquement ses propres notes.
     */
    public function view(User $user, ESBTPNote $note)
    {
        // L'accès personnel suit la propriété réelle de la note, quel que soit
        // le nom du rôle (un compte peut aussi être enseignant/coordinateur).
        if ($user->can('notes.view_own')
            && $note->etudiant
            && (int) $note->etudiant->user_id === (int) $user->id) {
            return true;
        }

        // Conserver la portée métier de l'enseignant : son droit notes.view
        // ne lui ouvre pas les évaluations des collègues. Le rôle étudiant
        // secondaire ne doit toutefois pas écraser ses permissions de prof.
        if ($user->can('notes.view_all')) {
            return true;
        }
        if ($user->hasRole(['teacher', 'enseignant'])) {
            return $user->can('notes.view') && $this->teacherOwnsEvaluation($user, $note);
        }

        // Lecture globale explicite pour les autres fonctions (y compris
        // un compte possédant aussi le rôle étudiant).
        return $user->can('notes.view');
    }

    /**
     * Enseignant: uniquement les notes de ses propres évaluations.
     * Coordinateur: interdit (logique métier existante).
     */
    public function update(User $user, ESBTPNote $note)
    {
        if (! $user->can('notes.edit')) {
            return false;
        }

        // Une permission d'édition seule ne permet pas à un enseignant
        // de modifier les évaluations confiées à ses collègues.
        if ($user->hasRole(['teacher', 'enseignant'])) {
            return $this->teacherOwnsEvaluation($user, $note);
        }

        // Le coordinateur n'est plus bloqué par son nom de rôle :
        // une permission notes.edit attribuée explicitement est effective.
        return true;
    }

    /**
     * Vérifie que l'enseignant est bien le propriétaire de l'évaluation liée à la note.
     */
    private function teacherOwnsEvaluation(User $user, ESBTPNote $note): bool
    {
        $evaluation = $note->evaluation;

        if (!$evaluation) {
            return false;
        }

        return $evaluation->enseignant_id === $user->id;
    }
}
