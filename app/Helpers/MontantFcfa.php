<?php

namespace App\Helpers;

use Illuminate\Support\HtmlString;

/**
 * Le montant tel qu'un courriel l'écrit : « 150 000 FCFA », jamais coupé.
 *
 * Trois gardes, parce qu'un montant coupé en fin de ligne se lit mal sur
 * téléphone (« 150 » en bas d'une ligne, « 000 FCFA » sur la suivante) :
 * - les milliers sont séparés par une espace fine insécable (U+202F), le
 *   séparateur de l'Imprimerie nationale ;
 * - l'unité est attachée par une espace insécable ;
 * - le tout est enveloppé dans un `white-space:nowrap`, que respectent les
 *   messageries qui ignoreraient l'insécabilité.
 *
 * Seule source du format des montants dans les avis aux parents.
 */
final class MontantFcfa
{
    /** Espace fine insécable, séparateur des milliers. */
    public const SEPARATEUR = "\u{202F}";

    /** « 150 000 », sans unité : le chiffre en vedette porte son unité à part. */
    public static function nombre(mixed $montant): string
    {
        return number_format(round((float) $montant), 0, ',', self::SEPARATEUR);
    }

    /** « 150 000 FCFA » insécable, prêt pour `{{ }}`. */
    public static function html(mixed $montant): HtmlString
    {
        return new HtmlString('<span style="white-space:nowrap">'.self::nombre($montant).'&nbsp;FCFA</span>');
    }
}
