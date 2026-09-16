<?php

namespace App\Domain\EmploiTemps;

final class DisponibiliteSalle
{
    public static function estFermee(?string $statut): bool
    {
        return $statut !== null && $statut !== '' && $statut !== 'available';
    }

    public static function conflitAvecPlanning(bool $fermee, bool $seanceSurCetteSalle): bool
    {
        return $fermee && $seanceSurCetteSalle;
    }
}
