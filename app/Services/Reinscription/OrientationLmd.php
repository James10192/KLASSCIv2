<?php

namespace App\Services\Reinscription;

/**
 * Quand l'année suivante ouvre plusieurs parcours dans la même mention,
 * l'étudiant doit choisir. S'il est déjà sur un parcours qui continue,
 * on ne rouvre pas le choix.
 *
 * L'orientation se lit dans les données, jamais dans le numéro d'année : une
 * école oriente après la L1, une autre dès l'entrée (Abidjan ouvre des L1
 * Bâtiment et Travaux Publics distinctes). Un tronc commun est un parcours
 * qui ne se poursuit pas l'année suivante ; c'est ce fait, et lui seul, qui
 * ouvre le choix.
 */
final class OrientationLmd
{
    /** @param  list<int>  $parcoursIdsAnneeSuivante */
    public static function doitProposerTousLesParcoursDeLaMention(
        ?int $parcoursQuitte,
        array $parcoursIdsAnneeSuivante
    ): bool {
        $ids = array_values(array_unique(array_filter($parcoursIdsAnneeSuivante)));
        if (count($ids) < 2) {
            return false;
        }

        if ($parcoursQuitte === null) {
            return true;
        }

        return ! in_array($parcoursQuitte, $ids, true);
    }
}
