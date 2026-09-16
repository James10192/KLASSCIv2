<?php

namespace App\Services\LMD\Tpe;

use App\Enums\TypeSeance;
use Carbon\Carbon;

final class TpeConstat
{
    public static function exigeSeanceEncadree(): bool
    {
        return TpePlanification::mode() === TpePlanification::MODE_SEANCE_ENCADREE;
    }

    public static function heuresRetenues(string $debut, string $fin): float
    {
        if ($debut === '' || $fin === '') {
            return 0.0;
        }

        $debutAt = Carbon::parse($debut);
        $finAt = Carbon::parse($fin);
        $heures = ($finAt->getTimestamp() - $debutAt->getTimestamp()) / 3600;

        return round(max(0, $heures), 2);
    }

    public static function nEstPasUneHeureEnseignantePayable(): bool
    {
        return ! TypeSeance::TPE->isVolumeTracked();
    }

    public static function seChevauchent(string $debutA, string $finA, string $debutB, string $finB): bool
    {
        $a1 = Carbon::parse($debutA);
        $a2 = Carbon::parse($finA);
        $b1 = Carbon::parse($debutB);
        $b2 = Carbon::parse($finB);

        return $a1->lt($b2) && $b1->lt($a2);
    }
}
