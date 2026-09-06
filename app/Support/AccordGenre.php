<?php

namespace App\Support;

/**
 * Accorder un mot au genre de la personne dont on parle.
 *
 * « Affectée », « inscrite », « nouvelle » : une école écrit à des familles, et
 * une fille lit son nom suivi d'un participe au masculin sur son propre dossier.
 * Ce n'est pas un détail de style — c'est la différence entre un document qui
 * parle d'elle et un document qui parle d'un dossier.
 *
 * ─── La frontière, et pourquoi elle compte plus que la table ───
 *
 * On accorde SEULEMENT quand le mot qualifie UNE personne identifiée. Les mêmes
 * mots servent ailleurs de noms de catégorie, et les accorder y serait une
 * faute :
 *
 *   « Non affecté » sur la fiche de Awa           -> « Non affectée »  ✔
 *   « Non affecté » en-tête d'une colonne de tarif -> reste au masculin ✘
 *   « Non affecté » sur un bouton de filtre        -> reste au masculin ✘
 *   « 12 non affectés » sur un compteur            -> reste au masculin ✘
 *
 * D'où une méthode qui EXIGE qu'on lui passe un genre. On ne peut pas l'appeler
 * « au cas où » : il faut avoir une personne sous la main. Un en-tête de colonne
 * n'en a pas, donc il ne peut pas l'appeler par accident.
 *
 * ─── Pourquoi une table de paires, et pas « on ajoute un e » ───
 *
 * Parce que le français n'ajoute pas toujours un e : nouveau devient nouvelle,
 * ancien devient ancienne, boursier devient boursière, admis devient admise.
 * Une règle mécanique produirait « nouveaue » et « ancienne » une fois sur deux.
 * La table ne couvre que les mots que ce logiciel écrit réellement ; un mot
 * inconnu ressort INCHANGÉ, ce qui est toujours préférable à une invention.
 */
class AccordGenre
{
    /**
     * Les paires que ce logiciel écrit réellement, en minuscules.
     *
     * N'ajouter un mot ici que lorsqu'un écran ou un document l'écrit au contact
     * d'une personne. Une table qui grossit « au cas où » finit par accorder des
     * en-têtes de tableau.
     */
    private const FEMININS = [
        // Statut d'affectation de l'État — le cas qui a motivé tout ceci.
        'affecté' => 'affectée',
        'réaffecté' => 'réaffectée',
        'non affecté' => 'non affectée',
        'non-affecté' => 'non-affectée',

        // Parcours administratif
        'inscrit' => 'inscrite',
        'réinscrit' => 'réinscrite',
        'pré-inscrit' => 'pré-inscrite',
        'candidat' => 'candidate',
        'orienté' => 'orientée',
        'transféré' => 'transférée',
        'nouveau' => 'nouvelle',
        'ancien' => 'ancienne',

        // Parcours académique
        'admis' => 'admise',
        'ajourné' => 'ajournée',
        'exclu' => 'exclue',
        'redoublant' => 'redoublante',
        'diplômé' => 'diplômée',
        'sortant' => 'sortante',

        // Situation
        'boursier' => 'boursière',
        'délégué' => 'déléguée',
        'étudiant' => 'étudiante',
        'né' => 'née',
        'né le' => 'née le',
    ];

    /** Ce que l'école écrit dans la case « sexe » d'un état civil. */
    private const MASCULIN_LISIBLE = 'Masculin';
    private const FEMININ_LISIBLE = 'Féminin';

    /**
     * Le mot, accordé au genre donné.
     *
     * La casse d'origine est préservée : « Affecté » rend « Affectée »,
     * « affecté » rend « affectée », « AFFECTÉ » rend « AFFECTÉE ». Sans cela,
     * un titre en capitales redescendrait en minuscules au milieu d'une ligne.
     *
     * @param  string|null  $sexe  la valeur brute de la colonne : M, F, ou une
     *                             forme libre venue d'un import
     */
    public static function accorder(?string $mot, ?string $sexe): string
    {
        $mot = (string) $mot;

        if ($mot === '' || ! self::estFeminin($sexe)) {
            return $mot;
        }

        $feminin = self::FEMININS[mb_strtolower(trim($mot))] ?? null;

        if ($feminin === null) {
            // Mot inconnu : on ne devine pas. Rendre le masculin est une
            // imprécision ; inventer un féminin serait une faute imprimée.
            return $mot;
        }

        return self::memeCasse($mot, $feminin);
    }

    /**
     * Accorde une phrase entière, mot par mot.
     *
     * Utile pour un libellé composé — « Nouveau · Non affecté ». Les mots
     * absents de la table traversent intacts.
     */
    public static function accorderPhrase(?string $phrase, ?string $sexe): string
    {
        $phrase = (string) $phrase;

        if ($phrase === '' || ! self::estFeminin($sexe)) {
            return $phrase;
        }

        // Les expressions en deux mots d'abord — « non affecté » doit être vu
        // avant « affecté », sinon on obtiendrait « non affectée » par hasard et
        // « né le » ne serait jamais reconnu.
        foreach (self::FEMININS as $masculin => $feminin) {
            if (! str_contains($masculin, ' ') && ! str_contains($masculin, '-')) {
                continue;
            }

            $phrase = self::remplacerMot($phrase, $masculin, $feminin);
        }

        foreach (self::FEMININS as $masculin => $feminin) {
            if (str_contains($masculin, ' ') || str_contains($masculin, '-')) {
                continue;
            }

            $phrase = self::remplacerMot($phrase, $masculin, $feminin);
        }

        return $phrase;
    }

    /**
     * Le sexe en toutes lettres, pour un état civil.
     *
     * Les fiches imprimaient « M » et « F » bruts. Sur un document d'état civil
     * que signe une famille, cela se lit comme un code de base de données.
     */
    public static function libelle(?string $sexe): ?string
    {
        $brut = trim((string) $sexe);

        if ($brut === '') {
            return null;
        }

        return self::estFeminin($brut) ? self::FEMININ_LISIBLE : self::MASCULIN_LISIBLE;
    }

    /**
     * La colonne est un `enum('M','F')`, mais les imports et les reprises de
     * données ont fait passer d'autres formes. On les lit toutes plutôt que de
     * traiter « Féminin » comme un masculin.
     */
    public static function estFeminin(?string $sexe): bool
    {
        $brut = mb_strtolower(trim((string) $sexe));

        if ($brut === '') {
            return false;
        }

        return in_array($brut, ['f', 'fe', 'fem', 'femme', 'feminin', 'féminin', 'fille', 'mme', 'mlle'], true);
    }

    /**
     * Rend le remplacement au mot entier, pour ne pas transformer « affecté »
     * dans « non affecté » quand la phrase entière a déjà été traitée, ni
     * « inscrit » dans « inscription ».
     */
    private static function remplacerMot(string $phrase, string $masculin, string $feminin): string
    {
        $motif = '/(?<![\p{L}\-])'.preg_quote($masculin, '/').'(?![\p{L}])/iu';

        return preg_replace_callback(
            $motif,
            fn (array $trouve) => self::memeCasse($trouve[0], $feminin),
            $phrase
        ) ?? $phrase;
    }

    /**
     * Rend le féminin dans la casse du masculin d'origine.
     */
    private static function memeCasse(string $origine, string $feminin): string
    {
        if ($origine === mb_strtoupper($origine, 'UTF-8')) {
            return mb_strtoupper($feminin, 'UTF-8');
        }

        $premiere = mb_substr($origine, 0, 1, 'UTF-8');

        if ($premiere === mb_strtoupper($premiere, 'UTF-8')) {
            return mb_strtoupper(mb_substr($feminin, 0, 1, 'UTF-8'), 'UTF-8')
                .mb_substr($feminin, 1, null, 'UTF-8');
        }

        return $feminin;
    }
}
