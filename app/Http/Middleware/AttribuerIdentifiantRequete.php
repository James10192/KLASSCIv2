<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Un identifiant par requete : dans les journaux, dans l'en-tete de reponse,
 * et sur la page d'erreur (« code de suivi »).
 *
 * C'est ce code qui relie une demande de support a la ligne de journal qui
 * l'explique : l'ecole le transmet avec son signalement, et le Master le
 * retrouve des deux cotes. Une valeur entrante n'est reprise que si elle a la
 * forme d'un ULID ou d'un UUID, pour qu'un appelant ne puisse pas ecrire ce
 * qu'il veut dans nos journaux.
 *
 * Place avant LogRequests : sans cela, la premiere ligne de chaque requete
 * serait la seule a ne pas porter l'identifiant.
 */
class AttribuerIdentifiantRequete
{
    private const FORME = '/^([0-9A-HJKMNP-TV-Z]{26}|[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$/i';

    public function handle(Request $request, Closure $next)
    {
        $entrant = (string) $request->headers->get('X-Request-ID', '');
        $id = preg_match(self::FORME, $entrant) ? $entrant : (string) Str::ulid();

        $request->attributes->set('request_id', $id);
        Log::withContext(['request_id' => $id]);

        $reponse = $next($request);
        $reponse->headers->set('X-Request-ID', $id);

        return $reponse;
    }
}
