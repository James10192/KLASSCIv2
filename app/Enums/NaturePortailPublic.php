<?php

namespace App\Enums;

/**
 * Ce que pese un point d'entree du portail public.
 *
 * `Identite` porte l'identite de quelqu'un : un depot, une recherche de
 * dossier. `Catalogue` ne sert qu'une liste publique — des noms de filieres,
 * de niveaux, de nationalites. Aucune donnee d'etudiant n'y entre ni n'en sort,
 * il n'y a rien a enumerer, et deux SELECT indexes y repondent.
 *
 * Un enum, et pas un drapeau `string` compare en dur : le parametre vient du
 * fichier de routes, et `portail.public:candidatures,catalog` — un caractere de
 * moins — faisait silencieusement retomber /choix dans le seau strict des
 * envois. Ouvrir le formulaire recoutait alors un jeton de depot, et la file
 * devant le formulaire refermait le canal des envois : exactement la regression
 * que la separation des seaux a ete ecrite pour empecher, restauree sans que
 * rien ne le signale.
 */
enum NaturePortailPublic: string
{
    case Identite = 'identite';

    case Catalogue = 'catalogue';

    /**
     * Une nature inconnue retombe sur la plus stricte.
     *
     * Se tromper dans ce sens coute un seau trop etroit sur un point d'entree
     * de lecture — visible, et sans danger. Se tromper dans l'autre ouvrirait
     * un seau large sur un point d'entree qui ecrit.
     */
    public static function depuis(string $valeur): self
    {
        return self::tryFrom($valeur) ?? self::Identite;
    }
}
