<?php

namespace App\Domain\Achats;

final class SeparationAchats
{
    public static function peutViserPaiement(int $saisisseurId, int $viseurId): bool
    {
        return $saisisseurId !== $viseurId;
    }

    public static function changementIbanApresVisa(bool $visaObtenu, string $ibanAvant, string $ibanApres): bool
    {
        return $visaObtenu && $ibanAvant !== $ibanApres;
    }
}
