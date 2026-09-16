<?php

namespace App\Domain\Patrimoine;

use App\Domain\EmploiTemps\DisponibiliteSalle;

final class IndisponibiliteBien
{
    public static function remonteAuPlanning(string $nature, bool $indisponible): bool
    {
        return $indisponible && in_array($nature, ['salle', 'vehicule'], true);
    }

    public static function salleEnMaintenanceRemonte(string $statutSalle): bool
    {
        return DisponibiliteSalle::conflitAvecPlanning(DisponibiliteSalle::estFermee($statutSalle), true);
    }
}
