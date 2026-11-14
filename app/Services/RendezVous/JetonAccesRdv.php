<?php

namespace App\Services\RendezVous;

/**
 * Le jeton qui ouvre la convocation d'un dossier apres un code reussi.
 *
 * Signe avec la cle de l'application (une par ecole), il porte la reference
 * normalisee et son expiration — rien d'autre, et rien qui se lise comme une
 * identite. Il ne vaut que pour SON dossier : un jeton obtenu pour une
 * reference ne s'applique pas a une autre.
 */
final class JetonAccesRdv
{
    private const DUREE_SECONDES = 1800;

    public static function emettre(string $referenceNormalisee, ?int $maintenant = null): string
    {
        $charge = $referenceNormalisee.'|'.(($maintenant ?? time()) + self::DUREE_SECONDES);

        return self::base64url($charge).'.'.self::signature($charge);
    }

    public static function valide(?string $jeton, string $referenceNormalisee, ?int $maintenant = null): bool
    {
        if (! is_string($jeton) || substr_count($jeton, '.') !== 1) {
            return false;
        }

        [$encode, $signature] = explode('.', $jeton);
        $charge = base64_decode(strtr($encode, '-_', '+/'), true);
        if ($charge === false || ! hash_equals(self::signature($charge), $signature)) {
            return false;
        }

        [$reference, $expiration] = array_pad(explode('|', $charge, 2), 2, '');

        return hash_equals($reference, $referenceNormalisee)
            && ctype_digit($expiration)
            && (int) $expiration >= ($maintenant ?? time());
    }

    private static function signature(string $charge): string
    {
        return self::base64url(hash_hmac('sha256', 'rdv-acces|'.$charge, (string) config('app.key'), true));
    }

    private static function base64url(string $octets): string
    {
        return rtrim(strtr(base64_encode($octets), '+/', '-_'), '=');
    }
}
