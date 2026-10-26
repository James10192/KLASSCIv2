<?php

namespace App\Policies;

use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ESBTPSeanceCoursPolicy
{
    use HandlesAuthorization;


    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return (bool) $user->is_active && $user->can('timetables.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ESBTPSeanceCours  $eSBTPSeanceCours
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ESBTPSeanceCours $eSBTPSeanceCours)
    {
        if (! $user->is_active) {
            return false;
        }
        if ($user->can('timetables.view')) {
            return true;
        }
        if (! $user->can('timetables.view_own')) {
            return false;
        }

        // teacher_id pointe vers esbtp_teachers.id, PAS users.id.
        // Lire la relation locale, jamais un identifiant enseignant fourni par le client.
        if ($eSBTPSeanceCours->teacher
            && (int) $eSBTPSeanceCours->teacher->user_id === (int) $user->id) {
            return true;
        }

        // Un étudiant ne voit que les séances de sa classe et de son année
        // d'inscription. Un ID de séance connu ne constitue pas une autorisation.
        $emploi = $eSBTPSeanceCours->emploiTemps;
        $classeId = $eSBTPSeanceCours->classe_id ?: $emploi?->classe_id;
        $anneeId = $eSBTPSeanceCours->annee_universitaire_id ?: $emploi?->annee_universitaire_id;
        if (! $classeId || ! $anneeId) {
            return false;
        }
        if (($eSBTPSeanceCours->classe_id && $emploi?->classe_id
                && (int) $eSBTPSeanceCours->classe_id !== (int) $emploi->classe_id)
            || ($eSBTPSeanceCours->annee_universitaire_id && $emploi?->annee_universitaire_id
                && (int) $eSBTPSeanceCours->annee_universitaire_id !== (int) $emploi->annee_universitaire_id)) {
            return false;
        }

        return ESBTPInscription::query()
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeId)
            ->whereHas('etudiant', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('timetables.create') || $user->can('timetables.edit');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ESBTPSeanceCours  $eSBTPSeanceCours
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ESBTPSeanceCours $eSBTPSeanceCours)
    {
        return (bool) $user->is_active && $user->can('timetables.edit');
    }

    /**
     * Determine whether the user can edit the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ESBTPSeanceCours  $eSBTPSeanceCours
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function edit(User $user, ESBTPSeanceCours $eSBTPSeanceCours)
    {
        return (bool) $user->is_active && $user->can('timetables.edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ESBTPSeanceCours  $eSBTPSeanceCours
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ESBTPSeanceCours $eSBTPSeanceCours)
    {
        return $user->can('timetables.delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ESBTPSeanceCours  $eSBTPSeanceCours
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, ESBTPSeanceCours $eSBTPSeanceCours)
    {
        return $user->can('system.manage');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ESBTPSeanceCours  $eSBTPSeanceCours
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, ESBTPSeanceCours $eSBTPSeanceCours)
    {
        return $user->can('system.manage');
    }
}
