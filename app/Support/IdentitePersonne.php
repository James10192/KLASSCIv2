<?php

namespace App\Support;

use Illuminate\Support\Str;

final class IdentitePersonne
{
    public static function concordent(
        mixed $nomA,
        mixed $prenomsA,
        mixed $dateA,
        mixed $nomB,
        mixed $prenomsB,
        mixed $dateB,
    ): bool {
        $nom = self::nom($nomA);
        $prenoms = self::nom($prenomsA);

        return self::jour($dateA) === self::jour($dateB)
            && $nom !== '' && $nom === self::nom($nomB)
            && $prenoms !== '' && $prenoms === self::nom($prenomsB);
    }

    public static function nom(mixed $valeur): string
    {
        return preg_replace(
            '/\s+/',
            ' ',
            mb_strtoupper(Str::ascii(trim((string) $valeur)), 'UTF-8')
        );
    }

    public static function jour(mixed $valeur): string
    {
        if ($valeur instanceof \DateTimeInterface) {
            return $valeur->format('Y-m-d');
        }

        $texte = trim((string) $valeur);

        return strlen($texte) >= 10 ? substr($texte, 0, 10) : $texte;
    }
}
