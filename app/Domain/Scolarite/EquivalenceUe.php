<?php

namespace App\Domain\Scolarite;

final class EquivalenceUe
{
    public static function creditsAAjouterAuWallet(bool $dejaCapitalises, int $creditsProposes): int
    {
        if ($dejaCapitalises) {
            return 0;
        }

        return max(0, $creditsProposes);
    }
}
