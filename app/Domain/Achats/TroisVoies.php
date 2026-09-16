<?php

namespace App\Domain\Achats;

final class TroisVoies
{
    public static function quantiteFacturable(float $commandee, float $recue, float $dejaFacturee): float
    {
        $eligible = min($commandee, $recue);

        return max(0.0, round($eligible - $dejaFacturee, 4));
    }

    public static function reliquatCommande(float $commandee, float $recue): float
    {
        return max(0.0, round($commandee - $recue, 4));
    }

    public static function receptionNeDoublePasLeStock(bool $receptionADejaMouvemente, bool $factureDemandeMouvementStock): bool
    {
        return ! ($receptionADejaMouvemente && $factureDemandeMouvementStock);
    }

    public static function estFactureDefinitive(string $typePiece): bool
    {
        return $typePiece === 'facture';
    }
}
