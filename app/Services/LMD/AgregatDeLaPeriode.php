<?php

namespace App\Services\LMD;

/**
 * L'agrégat d'une période délibérée : un semestre, ou l'année entière.
 *
 * Un jury peut être semestriel ou annuel — `esbtp_lmd_jurys.semestre` est
 * nullable. Sur un jury annuel, plusieurs bulletins composent la période, et
 * les nombres qui figureront au procès-verbal doivent les agréger tous.
 *
 * Cette classe ne fait que l'arithmétique, sur des objets déjà chargés : pas
 * de requête, pas de dépendance au conteneur. Deux appelants s'en servent et
 * doivent trouver le MÊME résultat, sans quoi le garde d'émission refuserait
 * des procès-verbaux parfaitement justes :
 *
 *   - `JuryDeliberationService::calculerDecisionAuto()`, qui produit la décision ;
 *   - `JuryPvIssuanceGuard`, qui vérifie avant émission que la décision gelée
 *     dit encore ce que disent les bulletins.
 *
 * C'est précisément pour cette raison qu'elle est ici et non recopiée des deux
 * côtés : deux formules qui divergent d'un millième bloqueraient l'émission.
 */
final class AgregatDeLaPeriode
{
    /**
     * Écart toléré entre une moyenne gelée et la moyenne recalculée.
     *
     * Les moyennes sont arrondies au centième avant d'être écrites ; comparer
     * à l'égalité stricte ferait échouer sur un flottant reconstitué.
     */
    public const TOLERANCE_MOYENNE = 0.005;

    /**
     * Un bulletin par semestre, du plus ancien au plus récent.
     *
     * La déduplication n'est pas décorative : si un bulletin a été régénéré,
     * deux lignes portent le même semestre, et les sommer compterait ses
     * crédits deux fois. On garde le dernier écrit — ce que faisait déjà
     * l'ancien `orderByDesc('id')->first()` pour le cas à un seul bulletin.
     *
     * Partagée, parce que le calcul de la décision et le garde d'émission
     * doivent dédupliquer de la même façon pour trouver le même nombre.
     *
     * @param iterable<object> $bulletins
     * @return list<object>
     */
    public static function parSemestre(iterable $bulletins): array
    {
        $parSemestre = [];
        foreach ($bulletins as $bulletin) {
            $cle = (string) ($bulletin->semestre ?? '');
            $connu = $parSemestre[$cle] ?? null;
            if ($connu === null || (int) $bulletin->id >= (int) $connu->id) {
                $parSemestre[$cle] = $bulletin;
            }
        }

        ksort($parSemestre);

        return array_values($parSemestre);
    }

    /**
     * La moyenne de la période.
     *
     * Un seul bulletin : sa moyenne, telle quelle — le cas semestriel, de loin
     * le plus courant, se comporte exactement comme avant l'introduction de
     * cette classe, sans repasser par une pondération qui exigerait des crédits.
     *
     * Plusieurs bulletins : Σ(moyenne × crédits) / Σ crédits, la même formule
     * que la moyenne générale d'un bulletin, appliquée d'un cran plus haut.
     *
     * Rend `null` dès qu'un semestre de la période n'est pas calculable. C'est
     * volontaire : la décision part alors en `defere`, ce qui est la vérité.
     * Une moyenne arithmétique non pondérée donnerait un nombre plausible et
     * faux, et personne ne le reverrait jamais.
     *
     * @param iterable<object> $bulletins
     */
    public static function moyenne(iterable $bulletins): ?float
    {
        $liste = is_array($bulletins) ? $bulletins : iterator_to_array($bulletins);
        $liste = array_values($liste);

        if ($liste === []) {
            return null;
        }

        if (count($liste) === 1) {
            $moyenne = $liste[0]->moyenne_generale;

            return $moyenne !== null ? (float) $moyenne : null;
        }

        $points = 0.0;
        $credits = 0;
        foreach ($liste as $bulletin) {
            if ($bulletin->moyenne_generale === null
                || $bulletin->credits_totaux === null
                || (int) $bulletin->credits_totaux <= 0) {
                return null;
            }

            $points += (float) $bulletin->moyenne_generale * (int) $bulletin->credits_totaux;
            $credits += (int) $bulletin->credits_totaux;
        }

        return $credits > 0 ? round($points / $credits, 2) : null;
    }

    /**
     * Les crédits de la période sont-ils tous renseignés ?
     *
     * Exigé sur CHAQUE bulletin : s'il en manque un, c'est un semestre entier
     * qui manque, et se prononcer sur l'autre moitié serait se tromper de
     * dénominateur.
     *
     * @param iterable<object> $bulletins
     */
    public static function creditsDisponibles(iterable $bulletins): bool
    {
        $liste = is_array($bulletins) ? $bulletins : iterator_to_array($bulletins);

        if ($liste === []) {
            return false;
        }

        foreach ($liste as $bulletin) {
            if ($bulletin->credits_capitalises === null || $bulletin->credits_totaux === null) {
                return false;
            }
        }

        return true;
    }

    /** @param iterable<object> $bulletins */
    public static function creditsObtenus(iterable $bulletins): int
    {
        $total = 0;
        foreach ($bulletins as $bulletin) {
            $total += (int) $bulletin->credits_capitalises;
        }

        return $total;
    }

    /** @param iterable<object> $bulletins */
    public static function creditsAttendus(iterable $bulletins): int
    {
        $total = 0;
        foreach ($bulletins as $bulletin) {
            $total += (int) $bulletin->credits_totaux;
        }

        return $total;
    }

    /**
     * Deux moyennes désignent-elles le même résultat ?
     *
     * `null` des deux côtés vaut concordance : une période non calculable
     * aujourd'hui comme au moment de la délibération n'a pas dérivé.
     */
    public static function moyennesConcordent(?float $gelee, ?float $recalculee): bool
    {
        if ($gelee === null || $recalculee === null) {
            return $gelee === $recalculee;
        }

        return abs($gelee - $recalculee) <= self::TOLERANCE_MOYENNE;
    }
}
