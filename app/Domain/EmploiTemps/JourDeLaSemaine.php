<?php

namespace App\Domain\EmploiTemps;

/**
 * Le jour d'une séance, quel que soit le format sous lequel il a été écrit.
 *
 * ## Pourquoi cette classe existe
 *
 * `esbtp_seance_cours.jour` reçoit DEUX formats, selon l'écran de saisie :
 *
 *  - depuis l'emploi du temps (« Ajouter une séance »), une chaîne capitalisée,
 *    `Lundi` … `Samedi` — le formulaire émet le libellé, et la règle de
 *    validation est `string|max:20` ;
 *  - depuis la liste des séances, un entier `1` … `7` — le formulaire émet les
 *    clés d'un tableau `[1 => 'Lundi', …]`.
 *
 * Les deux écrans écrivent dans la même colonne. En PHP 8, `1 == 'Lundi'` est
 * **faux** : depuis la RFC « Saner string to number comparisons », c'est l'entier
 * qui est converti en chaîne, et `'1' == 'Lundi'` ne tient pas. Deux séances
 * saisies par les deux chemins ne se voient donc pas l'une l'autre.
 *
 * Le dépôt le savait déjà par endroits — `ESBTPEmploiTempsController` portait
 * deux résolveurs **privés**, donc inatteignables, et `ESBTPSeanceCours` se
 * défend par un `$joursMapping[strtolower(...)]`. Mais aux endroits qui
 * comparent, non : la détection de conflits comparait `==`, le filtre de la
 * liste interrogeait la colonne avec un entier, et deux méthodes du modèle
 * comparent en `!==` strict.
 *
 * Cette classe est le format canonique. Elle ne convertit rien en base : elle
 * ramène toute écriture existante à un même nombre, pour qu'on puisse la
 * comparer. Normaliser les lignes déjà écrites est un autre geste, qui touche
 * huit instances.
 *
 * ## Ce qu'elle ne sait pas
 *
 * Le dimanche. Aucun des deux formulaires ne le propose, les deux résolveurs
 * qu'elle remplace s'arrêtaient au samedi, et `7` y rendait déjà `null`. Le
 * reproduire est délibéré : rendre `6` pour un dimanche inventerait une
 * correspondance que personne n'a décidée. Une école qui ouvre le dimanche
 * ajoute le jour aux deux formulaires ET ici, d'un même geste.
 */
final class JourDeLaSemaine
{
    /** Du lundi au samedi, dans l'ordre. L'indice est le rang, base zéro. */
    private const LIBELLES = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

    /**
     * Les écritures acceptées, ramenées au rang.
     *
     * L'anglais y figure parce que les résolveurs remplacés l'acceptaient. Rien
     * dans le dépôt ne l'écrit aujourd'hui ; le retirer serait un pari sur des
     * données que je n'ai pas vues.
     */
    private const ECRITURES = [
        'lundi' => 0, 'monday' => 0,
        'mardi' => 1, 'tuesday' => 1,
        'mercredi' => 2, 'wednesday' => 2,
        'jeudi' => 3, 'thursday' => 3,
        'vendredi' => 4, 'friday' => 4,
        'samedi' => 5, 'saturday' => 5,
    ];

    /**
     * Le rang du jour, base zéro : lundi vaut 0, samedi vaut 5.
     *
     * Base zéro et non 1..7, parce que c'est ce dont les appelants ont besoin :
     * un décalage à ajouter au premier jour d'un emploi du temps.
     *
     * Rend `null` pour tout ce qui ne désigne aucun jour connu — y compris une
     * chaîne vide, `null`, ou un entier hors bornes. C'est volontaire : les deux
     * sites qui repliaient sur `0` faisaient passer un jour inconnu pour un
     * lundi, et posaient donc une date fausse sans le dire.
     */
    public static function rang(mixed $jour): ?int
    {
        if (is_numeric($jour)) {
            $rang = (int) $jour - 1;

            return isset(self::LIBELLES[$rang]) ? $rang : null;
        }

        if (is_string($jour)) {
            return self::ECRITURES[strtolower(trim($jour))] ?? null;
        }

        return null;
    }

    /**
     * Deux écritures désignent-elles le même jour ?
     *
     * Deux jours inconnus ne concordent PAS. Les tenir pour égaux replierait
     * toutes les données abîmées les unes sur les autres — c'est exactement le
     * défaut que la détection de conflits portait avec `null == null`.
     */
    public static function memeJour(mixed $jour, mixed $autre): bool
    {
        $rang = self::rang($jour);

        return $rang !== null && $rang === self::rang($autre);
    }

    /** Le libellé français, ou `null` si l'écriture ne désigne aucun jour connu. */
    public static function libelle(mixed $jour): ?string
    {
        $rang = self::rang($jour);

        return $rang !== null ? self::LIBELLES[$rang] : null;
    }

    /**
     * Toutes les écritures d'un jour, pour interroger une colonne non normalisée.
     *
     * Le filtre de la liste des séances envoie un entier ; la colonne porte
     * aussi des libellés. `whereIn('jour', JourDeLaSemaine::ecrituresDe(1))`
     * retrouve les deux, sans rien migrer.
     *
     * Seulement le français, alors que la LECTURE accepte aussi l'anglais :
     * cette liste sert à interroger la colonne, et rien dans le dépôt n'y écrit
     * l'anglais. L'y mettre élargirait chaque requête sans jamais rien
     * retrouver. Le jour où une écriture anglaise apparaît en base, c'est ici
     * qu'il faut l'ajouter — et le contrôle qui l'exclut est explicite.
     *
     * @return list<string> l'entier, puis le libellé capitalisé et en minuscules
     */
    public static function ecrituresDe(mixed $jour): array
    {
        $rang = self::rang($jour);

        if ($rang === null) {
            return [];
        }

        $libelle = self::LIBELLES[$rang];

        // Le libellé tel que le formulaire l'écrit (capitalisé), et tel qu'il
        // pourrait l'avoir été : la colonne n'est pas normalisée, et une
        // collation sensible à la casse rendrait les deux distincts.
        return array_values(array_unique([
            (string) ($rang + 1),
            $libelle,
            strtolower($libelle),
        ]));
    }
}
