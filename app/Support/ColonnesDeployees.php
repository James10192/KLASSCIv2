<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Une colonne ajoutee par une migration existe-t-elle deja ?
 *
 * Le deploiement enchaine pull, cache:clear, migrate : pendant quelques
 * secondes, le nouveau code tourne sur l'ancien schema. Les ecrans qui lisent
 * une colonne toute neuve s'en passent au lieu de rendre une erreur 500.
 *
 * « Presente » est retenue jusqu'au prochain cache:clear : une colonne ne
 * disparait pas. « Absente » ne l'est qu'une minute, sinon une requete servie
 * entre cache:clear et migrate masquerait la colonne jusqu'au deploiement
 * suivant.
 */
class ColonnesDeployees
{
    private const ABSENTE_TTL = 60;

    public static function existe(string $table, string $colonne): bool
    {
        $cle = 'schema-colonne:'.$table.'.'.$colonne;
        if (Cache::get($cle) === true) {
            return true;
        }

        $presente = Schema::hasColumn($table, $colonne);
        $presente
            ? Cache::forever($cle, true)
            : Cache::put($cle, false, self::ABSENTE_TTL);

        return $presente;
    }

    /**
     * La colonne si elle existe, rien sinon : pour une liste de colonnes a
     * charger (`with('relation:id,...')`).
     *
     * @return list<string>
     */
    public static function si(string $table, string $colonne): array
    {
        return self::existe($table, $colonne) ? [$colonne] : [];
    }
}
