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

    /**
     * S1.5 — Fenêtre d'annulation 5 minutes pour le caissier qui s'est trompé.
     *
     * Permet d'annuler son propre paiement sans déranger un comptable, à condition que :
     *   - Le user a saisi ce paiement (created_by == auth.id)
     *   - Le paiement est encore en_attente (pas encore validé/rejeté)
     *   - Saisi il y a moins de N minutes (configurable via setting tenant, default 5)
     *
     * C'est ANTI-ERREUR (typo cash, mauvais étudiant), pas anti-fraude — donc
     * pas besoin de permission supplémentaire. Le caissier annule SA SAISIE.
     *
     * Au-delà de N min, il faut passer par paiements.delete (rare, comptable only).
     */
    public function cancelOwnRecent(User $user, ESBTPPaiement $paiement): bool
    {
        if (! $user->can('paiements.create')) {
            return false;
        }

        if ((int) ($paiement->created_by ?? 0) !== (int) $user->id) {
            return false;
        }

        if ($paiement->status !== 'en_attente') {
            return false;
        }

        $windowMinutes = (int) \App\Helpers\SettingsHelper::get('comptabilite.cancel_own_window_minutes', 5);
        if ($windowMinutes <= 0) {
            return false;
        }

        return $paiement->created_at && $paiement->created_at->gt(now()->subMinutes($windowMinutes));
    }
}
