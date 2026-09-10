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
 *   permission:a            Spatie, une seule permission
 *   permission:a|b          Spatie, l'une des deux
 *   can:a                   Laravel, sans modele
 *   role:a|b                Spatie, l'un des roles
 *   role_or_permission:a|b  Spatie, l'un des roles ou l'une des permissions
 *
 * Les middlewares poses dans le constructeur d'un controleur comptent aussi :
 * `gatherMiddleware()` les rend avec ceux de la route. C'est la que se cachait
 * un `role:superAdmin` que la premiere version de cette classe ne voyait pas,
 * et l'entree « Secretaire » du menu Personnel menait toujours a un 403.
 *
 * Deux middlewares distincts veulent dire « les deux », un `|` a l'interieur
 * veut dire « l'une ». Tout le reste — `can:update,post`, un `role:` avec un
 * garde (`role:a,web`), une policy, un garde maison — est ILLISIBLE ici, et
 * `verdict()` repond alors `null` : a l'appelant de choisir, et le choix sur
 * qui n'a jamais blesse personne est de ne rien montrer.
 *
 * Ce qu'elle ne regarde pas : `auth`, `paywall`, `throttle` et les autres
 * gardes qui ne dependent pas d'une autorisation. Les surfaces qui l'appellent
 * sont elles-memes derriere eux — y etre parvenu prouve qu'ils sont franchis.
 */
final class PorteDeRoute
{
    /** Middlewares dont on sait extraire une exigence, et ce qu'ils exigent. */
    private const LISIBLES = [
        'permission' => 'permission',
        'can' => 'permission',
        'role' => 'role',
        'role_or_permission' => 'role_ou_permission',
    ];

    /** Une porte illisible ou absente est fermee, jamais ouverte au hasard. */
    public static function ouverte(string $nomDeRoute, ?Authorizable $utilisateur): bool
    {
        return self::verdict($nomDeRoute, $utilisateur) === true;
    }

    /**
     * `true` : la route le laisserait passer. `false` : elle le refuserait.
     * `null` : on ne sait pas lire — route absente, garde illisible, ou aucune
     * garde lisible (une policy et l'absence de garde se ressemblent d'ici).
     */
    public static function verdict(string $nomDeRoute, ?Authorizable $utilisateur): ?bool
    {
        if ($utilisateur === null) {
            return false;
        }

        $exigences = self::exigencesDe($nomDeRoute);

        if ($exigences === null || $exigences === []) {
            return null;
        }

        foreach ($exigences as [$nature, $alternatives]) {
            if (! self::satisfait($utilisateur, $nature, $alternatives)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Les exigences lisibles d'une route : une paire [nature, alternatives] par
     * middleware. `null` des qu'une garde d'autorisation n'est pas lisible ou
     * que la route n'existe pas.
     *
     * @return list<array{0: string, 1: list<string>}>|null
     */
    private static function exigencesDe(string $nomDeRoute): ?array
    {
        $route = Route::getRoutes()->getByName($nomDeRoute);

        if ($route === null) {
            return null;
        }

        $exigences = [];

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            [$nom, $arguments] = array_pad(explode(':', $middleware, 2), 2, null);

            if (! isset(self::LISIBLES[$nom])) {
                continue;
            }

            // `can:update,post` porte un modele, `role:a,web` un garde : on ne
            // sait pas les evaluer d'ici. Porte illisible.
            if ($arguments === null || $arguments === '' || str_contains($arguments, ',')) {
                return null;
            }

            $exigences[] = [self::LISIBLES[$nom], explode('|', $arguments)];
        }

        return $exigences;
    }

    /** @param list<string> $alternatives */
    private static function satisfait(Authorizable $utilisateur, string $nature, array $alternatives): bool
    {
        foreach ($alternatives as $alternative) {
            // `can()` et non `canAny()` : le contrat `Authorizable` ne declare
            // que le premier, le second vient du trait. Passer un tableau a
            // `can()` ne remplacerait pas `canAny()` non plus — Laravel y
            // exige TOUTES les permissions, quand un `|` en veut une seule.
            $franchi = match ($nature) {
                'permission' => $utilisateur->can($alternative),
                'role' => self::aLeRole($utilisateur, $alternative),
                'role_ou_permission' => $utilisateur->can($alternative) || self::aLeRole($utilisateur, $alternative),
            };

            if ($franchi) {
                return true;
            }
        }

        return false;
    }

    /**
     * `hasRole()` vient du trait Spatie, pas du contrat `Authorizable` : un
     * porteur qui ne l'a pas ne tient aucun role.
     */
    private static function aLeRole(Authorizable $utilisateur, string $role): bool
    {
        return method_exists($utilisateur, 'hasRole') && $utilisateur->hasRole($role);
    }
}
