<?php

namespace App\Services\Verification;

/**
 * Ce qu'on laisse voir d'une adresse ou d'un numero : de quoi reconnaitre le
 * sien (« k***@gmail.com »), pas de quoi l'ecrire.
 */
final class MasqueContact
{
    public static function email(?string $email): string
    {
        $email = trim((string) $email);
        $arobase = strrpos($email, '@');
        if ($arobase === false || $arobase === 0) {
            return '***';
        }

        return mb_substr($email, 0, 1).'***'.substr($email, $arobase);
    }

    public static function telephone(?string $telephone): string
    {
        $chiffres = preg_replace('/\D+/', '', (string) $telephone) ?? '';
        if (strlen($chiffres) < 6) {
            return '***';
        }

        $prefixe = str_starts_with((string) $telephone, '+') ? '+' : '';

        return $prefixe.substr($chiffres, 0, 5).str_repeat('*', strlen($chiffres) - 7).substr($chiffres, -2);
    }
}
