<?php

namespace App\Services\Admissions;

use App\Models\ESBTPCandidatureWorkflow;
use Illuminate\Support\Carbon;

/**
 * Expose l'état de vie du lien SANS supposer qu'un transport a livré le message.
 * Aucun jeton, URL signée ou secret n'est retourné à la vue.
 */
final class EtatInvitationCompteEtudiant
{
    /** @return array{code:string,libelle:string,expiration:?Carbon} */
    public static function lire(ESBTPCandidatureWorkflow $workflow): array
    {
        if ($workflow->accessActivated()) {
            return ['code' => 'active', 'libelle' => 'Compte activé', 'expiration' => null];
        }

        if ($workflow->activation_token_used_at !== null) {
            return ['code' => 'utilise', 'libelle' => 'Lien déjà utilisé', 'expiration' => null];
        }

        $expiration = $workflow->activation_token_expires_at;
        if (! $workflow->activation_token_hash || ! $expiration) {
            return ['code' => 'absent', 'libelle' => 'Aucun lien actif', 'expiration' => null];
        }

        if ($expiration->isPast()) {
            return ['code' => 'expire', 'libelle' => 'Lien expiré — nouvel envoi nécessaire', 'expiration' => $expiration];
        }

        return ['code' => 'cree', 'libelle' => 'Lien créé — réception non confirmée', 'expiration' => $expiration];
    }
}
