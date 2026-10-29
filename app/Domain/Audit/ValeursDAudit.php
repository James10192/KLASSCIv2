<?php

namespace App\Domain\Audit;

/**
 * old_values / new_values d'un audit, toujours en tableau.
 *
 * Le modele les caste, mais une lecture brute (requete, export) rend la chaine
 * JSON : les deux formes arrivent ici.
 */
final class ValeursDAudit
{
    /** @return array<string, mixed> */
    public static function de(mixed $valeurs): array
    {
        if (is_array($valeurs)) {
            return $valeurs;
        }
        if (is_string($valeurs) && $valeurs !== '') {
            $decode = json_decode($valeurs, true);

            return is_array($decode) ? $decode : [];
        }

        return [];
    }
}
