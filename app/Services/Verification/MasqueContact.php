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

    /**
     * Forme lisible pour un rapport : « +225 07 ** ** ** 12 ». Seuls
     * l'indicatif, les deux premiers et les deux derniers chiffres restent.
     */
    public static function telephoneGroupe(?string $telephone): string
    {
        $parties = \App\Domain\Notifications\PhoneNormalizer::decomposer($telephone);
        $national = $parties['national'] ?? (preg_replace('/\D+/', '', (string) $telephone) ?? '');
        if (strlen($national) < 6) {
            return '***';
        }

        $paires = str_split($national, 2);
        $dernier = count($paires) - 1;
        foreach ($paires as $i => $paire) {
            if ($i !== 0 && $i !== $dernier) {
                $paires[$i] = str_repeat('*', strlen($paire));
            }
        }

        return trim((isset($parties['indicatif']) ? '+'.$parties['indicatif'].' ' : '').implode(' ', $paires));
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
