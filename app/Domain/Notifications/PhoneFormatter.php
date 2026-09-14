<?php

namespace App\Domain\Notifications;

/**
 * Formate un numéro de téléphone pour l'affichage humain.
 * Pure compute, pas de dépendance Laravel. Complète PhoneNormalizer.
 */
final class PhoneFormatter
{
    /**
     * Format lisible « +225 07 07 12 34 56 » depuis n'importe quel input valide.
     * Retourne null si invalide (le caller affichera « — » ou similaire).
     *
     * L'indicatif rendu est CELUI QUI A ÉTÉ RECONNU, jamais un indicatif écrit
     * ici. Recoller « +225 » en dur neutralisait le normaliseur à l'écran et
     * dans l'export de recouvrement : un numéro béninois correctement analysé
     * ressortait ivoirien sur la fiche que le comptable lit avant d'appeler.
     */
    public static function toReadable(?string $raw): ?string
    {
        $parties = PhoneNormalizer::decomposer($raw);

        if ($parties === null) {
            return null;
        }

        // Indicatif inconnu de cette instance : on ne sait pas où le couper,
        // donc on rend la forme canonique telle quelle plutôt qu'un groupement
        // faux — « +33 61 23 45 678 » se lirait plus mal que « +33612345678 ».
        if ($parties['indicatif'] === null) {
            return '+'.$parties['national'];
        }

        return '+'.$parties['indicatif'].' '.implode(' ', str_split($parties['national'], 2));
    }
}
