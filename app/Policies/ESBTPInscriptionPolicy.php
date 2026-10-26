<?php

namespace App\Policies;

use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ESBTPInscriptionPolicy
{
    use HandlesAuthorization;


    public function viewAny(User $user)
    {
        return $user->can('inscriptions.view');
    }

    /**
     * Étudiant: uniquement sa propre inscription.
     * Enseignant: peut voir les inscriptions de ses classes (pas les données financières — géré dans le controller).
     * Secrétaire/comptable/caissier: toutes les inscriptions.
     */
    public function view(User $user, ESBTPInscription $inscription)
    {
        // Une permission globale explicite vaut même pour un compte ayant
        // aussi le rôle étudiant (multi-fonctions). Le rôle ne retire aucun
        // droit effectivement attribué par l'établissement.
        return $user->can('inscriptions.view');
    }

    /**
     * Vérifie si l'utilisateur peut voir les données financières de l'inscription.
     * Délègue à la porte unique `finances.etudiants.voir`.
     */
    public function viewFinancials(User $user, ESBTPInscription $inscription)
    {
        return $user->can('finances.etudiants.voir');
    }

    public function create(User $user)
    {
        return $user->can('inscriptions.create');
    }

    public function update(User $user, ESBTPInscription $inscription)
    {
        return $user->can('inscriptions.edit');
    }

    public function markInKind(User $user, ESBTPInscription $inscription)
    {
        return $user->can('inscriptions.in_kind.mark');
    }

    public function delete(User $user, ESBTPInscription $inscription)
    {
        return $user->can('inscriptions.delete');
    }
}
