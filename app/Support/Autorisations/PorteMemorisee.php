<?php

namespace App\Support\Autorisations;

use Illuminate\Auth\Access\Gate;
use Illuminate\Support\Arr;

/**
 * La porte d'autorisation de Laravel, qui se souvient de ses réponses le temps
 * d'une requête de lecture.
 *
 * Une page de liste pose la même question des centaines de fois : la liste des
 * inscriptions en posait 367, dont 96 distinctes, et chaque réponse repasse
 * par Spatie, le contrôle superAdmin, les capacités du service scolarité et
 * les accès temporaires. La réponse ne change pas d'une ligne à l'autre.
 *
 * La mémoire ne vaut que :
 *  - pour une question SANS argument (« peut-il voir les inscriptions ? ») —
 *    une question sur un objet précis dépend de cet objet ;
 *  - pendant une requête GET ou HEAD, ouverte par le middleware
 *    {@see OuvreLaMemoireDesAutorisations}. Une requête qui écrit peut changer
 *    les rôles de la personne qui la fait ; une lecture, non ;
 *  - pour la personne qui pose la question, identifiée par son id.
 *
 * Hors requête (console, files d'attente, tests unitaires), rien n'est retenu.
 */
class PorteMemorisee extends Gate
{
    public const ATTRIBUT = '_autorisations_memorisees';

    public function raw($ability, $arguments = [])
    {
        $arguments = Arr::wrap($arguments);

        if ($arguments !== [] || ! is_string($ability)) {
            return parent::raw($ability, $arguments);
        }

        $requete = $this->container->bound('request') ? $this->container['request'] : null;
        $memoire = $requete?->attributes->get(self::ATTRIBUT);
        $utilisateur = $this->resolveUser();

        if (! is_array($memoire) || ! $utilisateur || ! method_exists($utilisateur, 'getAuthIdentifier')) {
            return parent::raw($ability, $arguments);
        }

        $cle = $utilisateur->getAuthIdentifier().'|'.$ability;

        if (array_key_exists($cle, $memoire)) {
            return $memoire[$cle];
        }

        $resultat = parent::raw($ability, $arguments);

        $memoire[$cle] = $resultat;
        $requete->attributes->set(self::ATTRIBUT, $memoire);

        return $resultat;
    }
}
