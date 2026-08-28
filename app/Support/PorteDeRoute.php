<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Route;

/**
 * Cet utilisateur franchirait-il la porte d'une route nommee ?
 *
 * Sert a decider si l'on EMMENE quelqu'un quelque part, ou si on lui montre un
 * lien : ce sont les deux endroits ou l'on prejuge d'une autorisation qu'on
 * n'appliquera qu'apres.
 *
 * La reponse se lit sur la route elle-meme, et non sur une copie de ses
 * permissions. La liste du groupe qui garde les inscriptions apparait des
 * dizaines de fois dans le fichier de routes, en PLUSIEURS variantes qui ont
 * deja diverge entre elles — certaines portent `identity.enrollment_officer`,
 * d'autres non. Une copie de plus n'aurait pas risque de diverger un jour :
 * elle serait nee du mauvais cote de l'une d'elles, en silence, et seulement
 * pour le role concerne.
 *
 * Ce qu'elle sait lire, exhaustivement :
 *
 *   permission:a          Spatie, une seule permission
 *   permission:a|b        Spatie, l'une des deux
 *   can:a                 Laravel, sans modele
 *
 * Deux middlewares distincts veulent dire « les deux », un `|` a l'interieur
 * veut dire « l'une ». Tout le reste — `can:update,post`, une policy, un garde
 * maison — est ILLISIBLE ici, et une porte illisible est traitee comme fermee.
 * On retombe alors sur le comportement d'avant : pas de lien, pas de
 * redirection, jamais un 403 au visage de quelqu'un qui a suivi ce qu'on lui a
 * montre.
 *
 * Ce qu'elle ne regarde pas : `auth`, `paywall`, et les autres gardes qui ne
 * dependent pas d'une permission. Les deux surfaces qui l'appellent sont
 * elles-memes derriere eux — y etre parvenu prouve qu'ils sont franchis.
 */
final class PorteDeRoute
{
    /** Middlewares dont on sait extraire une exigence de permission. */
    private const LISIBLES = ['permission:', 'can:'];

    public static function ouverte(string $nomDeRoute, ?Authorizable $utilisateur): bool
    {
        if ($utilisateur === null) {
            return false;
        }

        $route = Route::getRoutes()->getByName($nomDeRoute);

        if ($route === null) {
            return false;
        }

        $exigences = [];

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            foreach (self::LISIBLES as $prefixe) {
                if (! str_starts_with($middleware, $prefixe)) {
                    continue;
                }

                $arguments = substr($middleware, strlen($prefixe));

                // `can:update,post` porte un modele : on ne sait pas l'evaluer
                // sans l'instance. Porte illisible, donc fermee.
                if (str_contains($arguments, ',')) {
                    return false;
                }

                $exigences[] = explode('|', $arguments);
            }
        }

        // Aucune exigence lisible : soit la route s'est ouverte a tous, soit
        // elle est gardee autrement — par une policy, par un middleware maison.
        // Les deux se ressemblent d'ici, et se tromper dans le sens permissif
        // rend un 403 a quelqu'un qui a clique sur ce qu'on lui a montre.
        if ($exigences === []) {
            return false;
        }

        foreach ($exigences as $alternatives) {
            // `can()` et non `canAny()` : le contrat `Authorizable` ne declare
            // que le premier, le second vient du trait. Passer un tableau a
            // `can()` ne remplacerait pas `canAny()` non plus — Laravel y
            // exige TOUTES les permissions, quand un `|` en veut une seule.
            $franchi = false;

            foreach ($alternatives as $permission) {
                if ($utilisateur->can($permission)) {
                    $franchi = true;
                    break;
                }
            }

            if (! $franchi) {
                return false;
            }
        }

        return true;
    }
}
