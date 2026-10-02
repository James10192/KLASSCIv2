<?php

namespace App\Http\Middleware;

use App\Support\Autorisations\PorteMemorisee;
use Closure;
use Illuminate\Http\Request;

/**
 * Ouvre, pour une requête de lecture seulement, la mémoire où
 * {@see PorteMemorisee} range ses réponses. Une requête qui écrit n'en a pas :
 * elle peut modifier les droits de celui qui la fait.
 */
class OuvreLaMemoireDesAutorisations
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $request->attributes->set(PorteMemorisee::ATTRIBUT, []);
        }

        return $next($request);
    }
}
