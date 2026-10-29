<?php

namespace App\Policies;

use App\Models\ESBTPMatiere;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ESBTPMatierePolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  \App\Models\User  $user
     * @param  string  $ability
     * @return void|bool
     */
    public function before(User $user, $ability)
    {
        // SuperAdmin a tous les droits
        if ($user->hasRole('superAdmin')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('matieres.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ESBTPMatiere  $eSBTPMatiere
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ESBTPMatiere $eSBTPMatiere)
    {
        return $user->can('matieres.view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('matieres.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ESBTPMatiere  $eSBTPMatiere
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ESBTPMatiere $eSBTPMatiere)
    {
        return $user->can('matieres.edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ESBTPMatiere  $eSBTPMatiere
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ESBTPMatiere $eSBTPMatiere)
    {
        return $user->can('matieres.delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ESBTPMatiere  $eSBTPMatiere
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, ESBTPMatiere $eSBTPMatiere)
    {
        return $user->can('system.manage');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ESBTPMatiere  $eSBTPMatiere
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, ESBTPMatiere $eSBTPMatiere)
    {
        return $user->can('system.manage');
    }
}
