<?php

namespace App\Domain\Support\Services;

use Carbon\Carbon;
use Throwable;

/**
 * Une date recue du Master, montree a l'heure de l'instance.
 *
 * Le Master envoie de l'ISO 8601 avec son propre decalage. Sans conversion, une
 * instance hors UTC (ucao-benin, UTC+1) affiche chaque heure en retard, sans
 * erreur. Et `Carbon::parse(null)` rend l'instant present : une date absente
 * doit rester absente.
 */
final class DateDuMaster
{
    public static function afficher(?string $iso, string $format = 'd M Y, H:i'): string
    {
        if ($iso === null || trim($iso) === '') {
            return '—';
        }

        try {
            return Carbon::parse($iso)->setTimezone(config('app.timezone'))->translatedFormat($format);
        } catch (Throwable) {
            return '—';
        }
    }
}
