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
 *
 * ## Les formules concurrentes qu'elle a remplacées
 *
 * La fiche étudiant en portait trois de plus, sur la MÊME page — l'indicateur
 * « Moy. générale », le diagramme de parcours et le bloc des années antérieures.
 * Elles divergeaient sur deux points, dont chacun rendait un nombre plus sûr que
 * la donnée qui le porte :
 *
 *  - un filtre `moyenne_generale > 0` écartait un semestre à 0,00. Or zéro est
 *    un résultat — celui de l'étudiant absent tout le semestre — et non une
 *    absence de résultat. Avec S1 à 8,50 et S2 à 0,00, il ne restait qu'un
 *    bulletin, donc la page annonçait 8,50 comme moyenne de l'ANNÉE, au lieu de
 *    4,25. Une de ces copies convertissait en plus ce nombre en mention
 *    officielle ;
 *  - un repli sur `avg()` rendait une moyenne arithmétique NON pondérée sous la
 *    même étiquette que la pondérée, sans le dire.
 *
 * Elles y passent toutes les trois. Un écran qui affiche deux fois la même
 * grandeur doit l'affirmer deux fois de la même façon : tant qu'elles
 * coexistaient, corriger l'une sans les autres aurait fait dire à une seule
 * fiche deux moyennes annuelles différentes pour un même étudiant.
 *
 * Ce que `parSemestre()` y fait, puisque la question se pose : rien, et c'est
 * voulu. Les requêtes de la fiche épinglent étudiant, classe et année, et
 * `esbtp_lmd_bulletins` porte un index UNIQUE sur ces trois colonnes plus le
 * semestre (`lmd_bulletin_unique`), que la régénération respecte par
 * `updateOrCreate` sur exactement cette clé — il ne peut donc pas y avoir deux
 * lignes à dédupliquer. Il est appelé par uniformité avec le jury, dont la
 * requête, elle, n'épingle pas toujours la classe.
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
     * Ce que la déduplication attrape, exactement — et ce qu'elle n'attrape pas.
     *
     * Elle NE sert PAS contre un bulletin régénéré : `esbtp_lmd_bulletins` porte
     * l'index UNIQUE `lmd_bulletin_unique` sur (etudiant_id, classe_id,
     * annee_universitaire_id, semestre), et `LMDBulletinService` régénère par
     * `updateOrCreate` sur exactement cette clé. Une régénération met la ligne à
     * jour, elle n'en crée pas de seconde. Cette précision est ici parce que la
     * phrase inverse y a figuré, et qu'elle était fausse.
     *
     * Elle sert contre le jury : `ESBTPLMDBulletin::scopeForJury()` n'épingle
     * `classe_id` que `->when($jury->classe_id, …)`. Un jury sans classe ramène
     * donc, pour un même étudiant et un même semestre, les bulletins de plusieurs
     * classes — et les sommer compterait leurs crédits deux fois. On garde le
     * dernier écrit.
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
