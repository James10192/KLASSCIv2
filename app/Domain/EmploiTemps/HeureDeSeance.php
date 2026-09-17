<?php

namespace App\Domain\EmploiTemps;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * L'heure d'une séance mise en forme pour l'écran, quel que soit son type.
 *
 * ## Pourquoi cette classe existe
 *
 * `heure_debut` n'a pas le même type selon le modèle qui la porte — c'est tout
 * le piège #14 de `klassci-debugging-discipline.md` :
 *
 *  - `ESBTPSeanceCours` la rend en `Carbon` **daté du jour** (accesseur
 *    `Carbon::parse`), donc sa mise en texte donne « 2026-09-17 08:00:00 » ;
 *  - `ESBTPAttendance` la rend en chaîne brute `'08:00:00'`, et `->format()`
 *    dessus lève une `Error` que `catch (\Exception)` ne rattrape pas.
 *
 * Un affichage juste doit donc connaître le type. Tant que chaque appelant le
 * redécouvre, la moitié se trompe — c'est ce qui a produit dix sites du piège.
 *
 * ## Ce que cette classe REFUSE de faire, et pourquoi c'est le point
 *
 * Elle ne se replie pas en silence. `optional($valeur)->format('H:i')` a l'air
 * de couvrir les deux cas ; il ne couvre que le `Carbon` : `Optional::__call`
 * ne délègue que `if (is_object($this->value))` et rend `null` autrement, sans
 * un mot. Posé ici, ce repli a **effacé un conflit réel du bandeau** — les
 * `null` entraient dans la clé de déduplication et repliaient deux lignes
 * distinctes en une. Le piège #12 de la même rule le dit : un rattrapage qui
 * dégrade l'affichage doit journaliser ce qu'il a rattrapé, sinon on ne cherche
 * même pas.
 *
 * D'où le `Log::warning` sur la seule branche qui perd de l'information.
 */
final class HeureDeSeance
{
    /**
     * L'heure au format « 08:00 », ou `null` si la valeur n'en porte pas.
     *
     * `null` en entrée rend `null` sans bruit : une heure absente est un cas
     * normal côté appelant, pas un rattrapage.
     */
    public static function hi(mixed $valeur): ?string
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        if ($valeur instanceof \DateTimeInterface) {
            return Carbon::instance($valeur)->format('H:i');
        }

        if (is_string($valeur)) {
            // `substr(…, 0, 5)` et non `Carbon::parse` : la chaîne vient d'une
            // colonne `time`, elle commence donc par « HH:MM ». La faire passer
            // par Carbon coûterait une analyse et, sur une valeur inattendue,
            // rendrait une heure inventée au lieu de rien.
            return substr($valeur, 0, 5);
        }

        // La seule branche qui perd de l'information. Elle se journalise.
        Log::warning('HeureDeSeance::hi a reçu un type qu\'il ne sait pas lire.', [
            'type' => get_debug_type($valeur),
        ]);

        return null;
    }
}
