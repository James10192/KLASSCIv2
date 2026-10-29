<?php

namespace App\Services\RendezVous;

use Carbon\Carbon;

final class CreneauRegle
{
    /**
     * @param  list<int>  $joursOuverts
     */
    public function __construct(
        public readonly Carbon $ouverture,
        public readonly Carbon $fermeture,
        public readonly Carbon $plancher,
        public readonly string $heureDebut,
        public readonly string $heureFin,
        public readonly int $dureeMinutes,
        public readonly int $capacite,
        public readonly array $joursOuverts,
        public readonly ?string $pauseDebut,
        public readonly ?string $pauseFin,
    ) {
    }
}
