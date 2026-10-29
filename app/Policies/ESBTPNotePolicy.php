<?php

namespace App\Policies;

use App\Models\ESBTPNote;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ESBTPNotePolicy
{
    use HandlesAuthorization;

    /**
     * Super-admin bypass — grants all abilities.
     */
    public function before(User $user, string $ability)
    {
        if ($user->hasRole('superAdmin')) {
            return true;
        }
    }

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
        if ($user->hasRole('etudiant')) {
            return $note->etudiant
                && $note->etudiant->user_id === $user->id;
        }

        if ($user->hasRole(['teacher', 'enseignant'])) {
            return $this->teacherOwnsEvaluation($user, $note);
        }

        return $user->can('module.notes_evaluations.access');
    }

    /**
     * Enseignant: uniquement les notes de ses propres évaluations.
     * Coordinateur: interdit (logique métier existante).
     */
    public function update(User $user, ESBTPNote $note)
    {
        if ($user->hasRole('coordinateur')) {
            return false;
        }

        if ($user->hasRole(['teacher', 'enseignant'])) {
            return $this->teacherOwnsEvaluation($user, $note);
        }

        return $user->can('module.notes_evaluations.access');
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
