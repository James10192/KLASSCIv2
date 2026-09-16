<?php

namespace App\Domain\Patrimoine;

use App\Domain\Stock\MouvementStock;

final class MissionRhEtLogistique
{
    public static function memeDeplacement(bool $memeAgent, bool $memeJour): bool
    {
        return $memeAgent && $memeJour;
    }

    public static function fusionInterdite(): bool
    {
        return true;
    }

    public static function piecesConsommeesTypeStock(): string
    {
        return MouvementStock::SORTIE_SERVICE;
    }
}
