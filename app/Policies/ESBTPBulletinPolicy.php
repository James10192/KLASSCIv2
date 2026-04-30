<?php

namespace App\Policies;

use App\Models\ESBTPBulletin;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ESBTPBulletinPolicy
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
        return $user->can('bulletins.view') || $user->can('bulletins.view_own');
    }

    /**
     * Étudiant: uniquement ses propres bulletins.
     * Enseignant/secrétaire/coordinateur: tous les bulletins (via permission).
     */
    public function view(User $user, ESBTPBulletin $bulletin)
    {
        if ($user->hasRole('etudiant')) {
            return $bulletin->etudiant
                && $bulletin->etudiant->user_id === $user->id;
        }

        return $user->can('bulletins.view') || $user->can('bulletins.view_own');
    }

    /**
     * Même logique que view pour le téléchargement PDF.
     */
    public function download(User $user, ESBTPBulletin $bulletin)
    {
        return $this->view($user, $bulletin);
    }

    public function create(User $user)
    {
        return $user->can('bulletins.create');
    }

    public function update(User $user, ESBTPBulletin $bulletin)
    {
        return $user->can('bulletins.edit');
    }

    public function delete(User $user, ESBTPBulletin $bulletin)
    {
        return $user->can('bulletins.delete');
    }
}
