<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequests
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('Incoming request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'input' => $request->except([
                'password', 'password_confirmation', 'current_password', '_token',
                // Portail public de reinscription : matricule + date de naissance
                // forment le facteur d'identification, et `ip_client` est
                // l'adresse reelle du visiteur. La base ne conserve cette
                // derniere que sous forme d'empreinte ; l'ecrire en clair dans
                // un journal non chiffre, lisible par le support et repris dans
                // les sauvegardes, annulerait cette precaution et fournirait un
                // annuaire de couples identifiants valides.
                'matricule', 'date_naissance', 'ip_client',
            ]),
            'route' => $request->route() ? $request->route()->getName() : null,
        ]);

        $response = $next($request);

        return $response;
    }
}
