<?php

namespace App\Domain\Assistant\Consommation;

/**
 * Tarifs déclarés des modèles (dollars par million de jetons), et conversion
 * en francs CFA. Ne sert que quand le fournisseur ne donne pas le coût exact.
 */
final class Tarifs
{
    public static function coutUsd(string $modele, int $entree, int $sortie, int $cache = 0): float
    {
        $tarif = (array) config("assistant.modeles.{$modele}.tarif", []);
        $prixEntree = (float) ($tarif['entree'] ?? 0);
        $prixSortie = (float) ($tarif['sortie'] ?? 0);
        // Jetons lus en cache : facturés au tarif cache s'il est déclaré, sinon au tarif plein.
        $prixCache = (float) ($tarif['cache'] ?? $prixEntree);
        $pleins = max(0, $entree - $cache);

        return ($pleins * $prixEntree + $cache * $prixCache + $sortie * $prixSortie) / 1_000_000;
    }

    public static function tauxUsdFcfa(): float
    {
        return (float) config('assistant.budget.taux_usd_fcfa', 600);
    }

    public static function enFcfa(float $usd): float
    {
        return round($usd * self::tauxUsdFcfa(), 2);
    }
}
