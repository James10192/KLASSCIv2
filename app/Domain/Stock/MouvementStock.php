<?php

namespace App\Domain\Stock;

use App\Domain\Achats\TroisVoies;

final class MouvementStock
{
    public const RECEPTION = 'reception';

    public const SORTIE_SERVICE = 'sortie_service';

    public const CESSION = 'cession';

    public const FACTURE = 'facture';

    public static function doitMouvoir(string $typeEvenement, bool $receptionADejaMouvemente): bool
    {
        if ($typeEvenement === self::FACTURE) {
            return TroisVoies::receptionNeDoublePasLeStock($receptionADejaMouvemente, true);
        }

        return in_array($typeEvenement, [self::RECEPTION, self::SORTIE_SERVICE, self::CESSION], true);
    }

    public static function sortieServiceNEstPasCession(string $type): bool
    {
        return $type === self::SORTIE_SERVICE;
    }
}
