<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * Un compteur par route, et non un seul par utilisateur.
 *
 * Sans nom de limiteur, `throttle:30,1` de Laravel 9 compte sur
 * sha1(identifiant utilisateur) seul : TOUTES les routes limitees de
 * l'application puisent dans le meme seau, chacune avec son propre plafond.
 * Mesure sur presentation (septembre 2026) : 32 ouvertures de grille de notes
 * (`throttle:60,1`), puis l'enregistrement d'une note (`throttle:30,1`) est
 * refuse en 429 — alors que l'enregistrement n'avait jamais ete appele.
 * Changer de matiere consommait le droit d'enregistrer.
 *
 * Chaque `throttle:N,M` pose sur une route voulait dire « N par minute sur
 * cette route » ; c'est ce que ce middleware fait. Les limiteurs nommes
 * (`throttle:api`, `throttle:exports`…) ne passent pas par cette signature et
 * gardent la cle que leur definition leur donne.
 */
class ThrottleRequestsParRoute extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        $signature = parent::resolveRequestSignature($request);

        $route = $request->route();
        if (! $route) {
            return $signature;
        }

        $identite = $route->getName()
            ?: implode('|', $route->methods()).' '.$route->uri();

        return sha1($signature.'|'.$identite);
    }
}
