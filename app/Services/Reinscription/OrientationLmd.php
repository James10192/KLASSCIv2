<?php

namespace App\Services\Reinscription;

/**
 * Quand l'année suivante ouvre plusieurs parcours dans la même mention,
 * l'étudiant doit choisir. S'il est déjà sur un parcours qui continue,
 * on ne rouvre pas le choix.
 */
final class OrientationLmd
{
    /** @param  list<int>  $parcoursIdsAnneeSuivante */
    public static function doitProposerTousLesParcoursDeLaMention(
        ?int $parcoursQuitte,
        array $parcoursIdsAnneeSuivante,
        bool $anneeDOrientation = false
    ): bool {
        $ids = array_values(array_unique(array_filter($parcoursIdsAnneeSuivante)));
        if (count($ids) < 2) {
            return false;
        }

        if ($anneeDOrientation || $parcoursQuitte === null) {
            return true;
        }

        return ! in_array($parcoursQuitte, $ids, true);
    }

    public static function estAnneeDOrientation(int $year, string $type): bool
    {
        $debut = \App\Models\ESBTPNiveauEtude::ANNEES_PAR_CYCLE_LMD[$type][0] ?? null;

        return $debut !== null && $year === $debut;
    }
}
