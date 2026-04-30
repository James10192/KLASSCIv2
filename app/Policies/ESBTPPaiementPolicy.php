<?php

namespace App\Policies;

use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ESBTPPaiementPolicy
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
        return $user->can('paiements.view') || $user->can('paiements.view_own');
    }

    /**
     * Record-level :
     *  - `paiements.view`     → voit tous les paiements
     *  - `paiements.view_own` → voit uniquement les paiements qu'il a encaissés (created_by)
     *  - étudiant             → ne voit que ses propres paiements (relation etudiant.user_id)
     */
    public function view(User $user, ESBTPPaiement $paiement)
    {
        if ($user->can('paiements.view')) {
            if ($user->hasRole('etudiant')) {
                return $paiement->etudiant
                    && $paiement->etudiant->user_id === $user->id;
            }

            return true;
        }

        if ($user->can('paiements.view_own')) {
            return (int) $paiement->created_by === (int) $user->id;
        }

        return false;
    }

    public function create(User $user)
    {
        return $user->can('paiements.create');
    }

    public function update(User $user, ESBTPPaiement $paiement)
    {
        return $user->can('paiements.edit');
    }

    public function delete(User $user, ESBTPPaiement $paiement)
    {
        return $user->can('paiements.delete');
    }
}
