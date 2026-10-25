<?php

namespace App\Http\Middleware;

use App\Support\ProfilActif;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige un contexte d'affichage pour les comptes à plusieurs fonctions.
 * Les permissions continuent à être vérifiées sur CHAQUE endpoint métier.
 */
final class ExigerChoixProfil
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! ProfilActif::doitChoisir($user) || ProfilActif::courant($request) !== null) {
            return $next($request);
        }

        // Ne jamais interférer avec le second facteur, la récupération de
        // session, le changement de mot de passe ou une nouvelle connexion SSO.
        if ($request->routeIs(
            'profil.choisir', 'profil.activer', 'logout',
            'securite.double-auth.*', 'password.change.*', 'auth.sso-from-group'
        )) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Choisissez votre profil de travail pour poursuivre.',
                'redirect' => route('profil.choisir'),
            ], 409);
        }

        return redirect()->guest(route('profil.choisir'));
    }
}
