<?php

namespace App\Domain\Enseignants;

use Carbon\Carbon;

final class HeuresConstatees
{
    public static function dureeHeures(?string $planDebut, ?string $planFin, ?string $reelDebut = null, ?string $reelFin = null): float
    {
        $debut = ($reelDebut !== null && $reelDebut !== '') ? $reelDebut : $planDebut;
        $fin = ($reelFin !== null && $reelFin !== '') ? $reelFin : $planFin;
        if ($debut === null || $debut === '' || $fin === null || $fin === '') {
            return 0.0;
        }

        $minutes = abs(Carbon::parse($fin)->diffInMinutes(Carbon::parse($debut)));

        return round($minutes / 60, 2);
    }
}
