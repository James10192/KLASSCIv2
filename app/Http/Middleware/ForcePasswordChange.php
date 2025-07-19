<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Vérifier si l'utilisateur est connecté
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Exclure les routes de changement de mot de passe et de déconnexion
        $excludedRoutes = [
            'password.change.form',
            'password.change.update',
            'logout',
            'login',
            'register',
        ];

        $currentRoute = $request->route() ? $request->route()->getName() : '';

        // Si la route actuelle est exclue, continuer
        if (in_array($currentRoute, $excludedRoutes)) {
            return $next($request);
        }

        // Vérifier si l'utilisateur doit changer son mot de passe
        if ($user->must_change_password) {
            // Rediriger vers la page de changement de mot de passe
            return redirect()->route('password.change.form')
                ->with('warning', 'Vous devez changer votre mot de passe pour continuer.');
        }

        return $next($request);
    }
}
