<?php

namespace App\Services\Verification;

/**
 * Le code a six chiffres et le jeton du lien, et leurs empreintes.
 *
 * Seule l'empreinte est stockee : HMAC-SHA256 sur la cle applicative. Une
 * fuite de la base ne donne ni les codes ni les liens en cours, et une
 * empreinte d'une instance ne vaut rien sur une autre.
 */
final class SecretsVerification
{
    public static function nouveauCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function nouveauJeton(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public static function empreinte(string $secret): string
    {
        return hash_hmac('sha256', $secret, (string) config('app.key'));
    }

    public static function concorde(?string $empreinte, string $secret): bool
    {
        return is_string($empreinte) && $empreinte !== '' && hash_equals($empreinte, self::empreinte($secret));
    }
}
