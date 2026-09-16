<?php

namespace App\Domain\Comptabilite\Paie;

final class ProfilPaysPaie
{
    public const CI = 'CI';

    public const BJ = 'BJ';

    public const NON_VALIDE = 'non_valide';

    public static function depuis(?string $valeur, string $defaut = self::CI): string
    {
        $valeur = trim((string) $valeur);

        return $valeur === '' ? $defaut : $valeur;
    }

    public static function permetPaiementDefinitif(string $profil): bool
    {
        return $profil !== self::NON_VALIDE;
    }

    public static function appliqueRetenuesIvoiriennes(string $profil): bool
    {
        return $profil === self::CI;
    }
}
