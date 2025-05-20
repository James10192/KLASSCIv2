<?php

namespace App\Policies;

use App\Models\ESBTPSeanceCours;
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
        return $user->hasRole('superAdmin') ||
               $user->hasRole('secretaire') ||
               $user->hasRole('teacher') ||
               $user->hasRole('etudiant');
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
        return $user->hasRole('superAdmin') ||
               $user->hasRole('secretaire') ||
               $user->hasRole('teacher') ||
               $user->hasRole('etudiant');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasRole('superAdmin') || $user->hasRole('secretaire');
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
        return $user->hasRole('superAdmin') || $user->hasRole('secretaire');
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
        return $user->hasRole('superAdmin') || $user->hasRole('secretaire');
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
        return $user->hasRole('superAdmin') || $user->hasRole('secretaire');
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
        return $user->hasRole('superAdmin');
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
        return $user->hasRole('superAdmin');
    }
}
