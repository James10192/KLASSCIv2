<?php

namespace App\Helpers;

/**
 * Une donnée d'avis est-elle réellement connue ?
 *
 * Les appelants des avis aux parents remplacent une donnée manquante par
 * « N/A » (`$classe->name ?? 'N/A'`). Écrit tel quel, cela donne « réinscrit(e)
 * en N/A » ou « rejeté le N/A ». Un gabarit demande donc la valeur, et retire
 * le morceau de phrase quand elle n'est pas connue.
 */
final class ValeurConnue
{
    private const INCONNUES = ['', 'N/A'];

    /** La valeur en texte, ou null si elle est vide ou vaut « N/A ». */
    public static function ou(mixed $valeur): ?string
    {
        if ($valeur === null || is_array($valeur) || (is_object($valeur) && ! method_exists($valeur, '__toString'))) {
            return null;
        }
        $texte = trim((string) $valeur);

        return in_array($texte, self::INCONNUES, true) ? null : $texte;
    }
}
