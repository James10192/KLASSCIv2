<?php

namespace App\Policies;

use App\Models\ESBTPBulletin;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ESBTPBulletinPolicy
{
    use HandlesAuthorization;


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
        // Une permission de lecture globale prime sur une simple identité
        // « étudiant » : un compte multi-rôles conserve l'union des droits.
        if ($user->can('bulletins.view')) {
            return true;
        }

        return $user->can('bulletins.view_own')
            && $bulletin->etudiant
            && (int) $bulletin->etudiant->user_id === (int) $user->id;
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
