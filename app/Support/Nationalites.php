<?php

namespace App\Support;

/**
 * Le vocabulaire des nationalites, sous les trois formes qu'on lui demande.
 *
 * Elles sortent toutes de config/nationalites.php. Les separer serait la
 * garantie d'une divergence : le formulaire d'inscription compare les valeurs
 * a l'identique, accents et majuscules compris, et une entree presente d'un
 * cote mais pas de l'autre donne un champ obligatoire qui retombe a vide sans
 * que personne ne le voie.
 */
class Nationalites
{
    /** Les groupes tels quels : c'est ce dont la vue a besoin pour ses optgroups. */
    public static function groupes(): array
    {
        return config('nationalites', []);
    }

    /**
     * Les valeurs stockees, a plat et DEDUPLIQUEES.
     *
     * Le select de l'ecole repete a dessein certaines nationalites sous
     * plusieurs entetes regionaux — « Sud-Africaine » figure sous Afrique
     * anglophone et sous Afrique australe. Sous un entete, c'est de la
     * navigation ; a plat, ce sont des doublons.
     */
    public static function valeurs(): array
    {
        return collect(self::groupes())
            ->flatMap(fn (array $groupe) => array_keys($groupe['entrees']))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Valeur et libelle, a plat, pour un selecteur.
     *
     * Dedupliquee, pour la meme raison que valeurs() : sans entetes, une
     * nationalite vue trois fois dans la meme liste deroulante n'est plus une
     * facilite de navigation, c'est une faute. « Ivoirienne », que presque
     * tout le monde choisit, apparaissait en premiere et en quatre-vingt-
     * douzieme position.
     *
     * Le libelle porte le drapeau : c'est ce qui rend une longue liste
     * parcourable a l'oeil.
     *
     * @return list<array{valeur:string,libelle:string}>
     */
    public static function pourSelecteur(): array
    {
        return collect(self::groupes())
            ->flatMap(fn (array $groupe) => collect($groupe['entrees'])
                ->map(fn (string $drapeau, string $valeur) => [
                    'valeur' => $valeur,
                    'libelle' => trim($drapeau.' '.$valeur),
                ])
                ->values())
            ->unique('valeur')
            ->values()
            ->all();
    }
}
