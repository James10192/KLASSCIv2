<?php

namespace App\Domain\Enseignants;

final class TitulaireOuRemplacant
{
    public static function paye(?int $titulaireId, ?int $remplacantId): ?int
    {
        return $remplacantId ?: $titulaireId;
    }
}
