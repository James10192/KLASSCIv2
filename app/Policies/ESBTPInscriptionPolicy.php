<?php

namespace App\Policies;

use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ESBTPInscriptionPolicy
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
        return $user->can('inscriptions.view');
    }

    /**
     * Étudiant: uniquement sa propre inscription.
     * Enseignant: peut voir les inscriptions de ses classes (pas les données financières — géré dans le controller).
     * Secrétaire/comptable/caissier: toutes les inscriptions.
     */
    public function view(User $user, ESBTPInscription $inscription)
    {
        if (!$user->can('inscriptions.view')) {
            return false;
        }

        if ($user->hasRole('etudiant')) {
            return $inscription->etudiant
                && $inscription->etudiant->user_id === $user->id;
        }

        return true;
    }

    /**
     * Vérifie si l'utilisateur peut voir les données financières de l'inscription.
     * Seuls les rôles financiers (comptable, caissier, secrétaire) y ont accès.
     */
    public function viewFinancials(User $user, ESBTPInscription $inscription)
    {
        return $user->hasAnyPermission([
            'paiements.view',
            'comptabilite.access',
            'comptabilite.dashboard.view',
        ]);
    }

    public function create(User $user)
    {
        return $user->can('inscriptions.create');
    }

    public function update(User $user, ESBTPInscription $inscription)
    {
        return $user->can('inscriptions.edit');
    }

    public function delete(User $user, ESBTPInscription $inscription)
    {
        return $user->can('inscriptions.delete');
    }
}
