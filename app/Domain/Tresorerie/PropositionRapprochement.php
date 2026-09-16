<?php

namespace App\Domain\Tresorerie;

final class PropositionRapprochement
{
    public static function peutProposer(bool $memeReference, bool $memeMontant, bool $memeDate): bool
    {
        return $memeReference || ($memeMontant && $memeDate);
    }

    public static function peutFusionnerAutomatiquement(bool $memeReference, bool $memeMontant, bool $memeDate): bool
    {
        return $memeReference;
    }
}
