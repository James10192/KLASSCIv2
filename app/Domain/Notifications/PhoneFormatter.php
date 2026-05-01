<?php

namespace App\Domain\Notifications;

/**
 * Formate les numéros de téléphone CI pour affichage humain.
 * Pure compute, pas de dépendance Laravel. Complète PhoneNormalizer.
 */
final class PhoneFormatter
{
    /**
     * Format lisible "+225 07 07 12 34 56" depuis n'importe quel input valide.
     * Retourne null si invalide (le caller affichera "—" ou similaire).
     */
    public static function toReadable(?string $raw): ?string
    {
        $e164 = PhoneNormalizer::toE164($raw);
        if ($e164 === null) {
            return null;
        }

        // E.164 = +225XXXXXXXXXX (3 chiffres pays + 10 nationaux)
        $national = substr($e164, 4);
        $pairs = str_split($national, 2);

        return '+225 ' . implode(' ', $pairs);
    }
}
