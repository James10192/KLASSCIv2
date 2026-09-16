<?php

namespace App\Domain\Achats;

final class PaiementIndetermine
{
    public static function peutRelancer(string $etat): bool
    {
        return ! in_array($etat, ['inconnu', 'en_cours'], true);
    }
}
