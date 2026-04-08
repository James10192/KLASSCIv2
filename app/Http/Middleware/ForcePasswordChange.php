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

        // Vérifier si l'utilisateur doit changer son mot de passe (premier login)
        if ($user->must_change_password) {
            return redirect()->route('password.change.form')
                ->with('password_change_reason', 'first_login')
                ->with('warning', 'Bienvenue ! Pour sécuriser votre compte, veuillez créer un mot de passe personnalisé.');
        }

        // Vérifier si le mot de passe est expiré (> X mois)
        if (\App\Services\UserService::isPasswordExpired($user)) {
            return redirect()->route('password.change.form')
                ->with('password_change_reason', 'expired')
                ->with('warning', 'Votre mot de passe a expiré. Pour la sécurité de votre compte, veuillez en créer un nouveau.');
        }

        return $next($request);
    }
}
