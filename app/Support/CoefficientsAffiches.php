<?php

namespace App\Support;

/**
 * La somme des coefficients d'un releve de resultats.
 *
 * Elle existait a deux endroits qui ne disaient pas la meme chose. La carte de
 * synthese sommait `total_coefficients`, la colonne brute de la ligne de
 * resultat ; le tableau juste en dessous sommait `matiere_coefficient`, le
 * coefficient configure par l'ecole. Sur les lignes nees avant le correctif du
 * coefficient, la colonne brute vaut 1 : la carte affichait donc le NOMBRE de
 * matieres deguise en somme des coefficients, a quelques centimetres d'un total
 * different.
 *
 * `total_coefficients` porte d'ailleurs deux sens selon le chemin — somme des
 * coefficients d'EVALUATIONS pour une moyenne calculee, coefficient de la
 * MATIERE pour une moyenne saisie a la main. Seul le second a un sens ici ;
 * c'est pourquoi le coefficient configure prime, et que la colonne brute ne sert
 * que de repli.
 */
final class CoefficientsAffiches
{
    /**
     * Le total affiche par la carte de synthese — sur le MEME perimetre que le
     * tableau qui la suit.
     *
     * Unifier la formule ne suffisait pas : sur l'onglet annuel, le tableau rend
     * un bloc par semestre a partir de `$annualSubjectBlocks`, tandis que la
     * liste a plat reste scopee au semestre primaire. La carte annoncait donc le
     * total d'un semestre au-dessus d'un tableau qui en affichait deux — la
     * contradiction d'origine, deplacee d'un onglet.
     *
     * @param  iterable<int|string, array<string, mixed>>  $blocs  blocs par semestre, vides hors onglet annuel
     * @param  iterable<int|string, array<string, mixed>>  $matieresAPlat  le jeu unique des autres onglets
     */
    public static function sommeAffichee(iterable $blocs, iterable $matieresAPlat): float
    {
        $blocs = is_array($blocs) ? $blocs : iterator_to_array($blocs);

        if ($blocs === []) {
            return self::somme($matieresAPlat);
        }

        $somme = 0.0;
        foreach ($blocs as $bloc) {
            $somme += self::somme($bloc['subjects'] ?? []);
        }

        return $somme;
    }

    /**
     * @param  iterable<int|string, array<string, mixed>>  $matieres
     */
    public static function somme(iterable $matieres): float
    {
        $somme = 0.0;

        foreach ($matieres as $matiere) {
            $somme += self::pourUneMatiere($matiere);
        }

        return $somme;
    }

    /**
     * @param  array<string, mixed>  $matiere
     */
    public static function pourUneMatiere(array $matiere): float
    {
        // `??` et non `?:` : un coefficient configure a zero est une decision de
        // l'ecole — un etudiant dispense — et non une absence de configuration.
        $coefficient = $matiere['matiere_coefficient'] ?? $matiere['total_coefficients'] ?? 0;

        return (float) $coefficient;
    }
}
