<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Force l'en-tête Accept: application/json sur les requêtes AJAX.
 *
 * Ce middleware tourne AVANT VerifyCsrfToken et auth pour que tous les
 * middlewares en aval détectent correctement $request->expectsJson()
 * et retournent du JSON au lieu de rediriger vers du HTML.
 */
class ForceJsonOnAjax
{
    public function handle(Request $request, Closure $next)
    {
        $isAjaxRoute = str_contains($request->path(), 'ajax')
            || str_contains($request->path(), 'api/');

        if ($isAjaxRoute || $request->ajax()) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
