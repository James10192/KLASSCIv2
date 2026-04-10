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
        return $user->can('paiements.view');
    }

    /**
     * Record-level: étudiant ne voit que ses propres paiements.
     * Comptable/secrétaire/caissier voient tout (via permission).
     */
    public function view(User $user, ESBTPPaiement $paiement)
    {
        if (!$user->can('paiements.view')) {
            return false;
        }

        if ($user->hasRole('etudiant')) {
            return $paiement->etudiant
                && $paiement->etudiant->user_id === $user->id;
        }

        return true;
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
