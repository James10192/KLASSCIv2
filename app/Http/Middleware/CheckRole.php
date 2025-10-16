<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Vérifie si l'utilisateur a le rôle spécifié.
     *
     * Ce middleware permet de restreindre l'accès aux routes en fonction du rôle de l'utilisateur.
     * Par exemple, certaines routes ne sont accessibles qu'aux super administrateurs, secrétaires ou étudiants.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        // Vérifier si l'utilisateur est connecté
        if (!Auth::check()) {
            return redirect('login');
        }

        // Récupérer l'utilisateur connecté
        $user = Auth::user();

        // Si l'utilisateur est superAdmin, il a accès à tout
        if ($user->hasRole('superAdmin')) {
            return $next($request);
        }

        // Vérifier pour les rôles multiples (ex: 'role:admin,editor' ou 'role:admin|editor')
        // Accepter à la fois les virgules ET les pipes comme séparateurs
        $roles = preg_split('/[,|]/', $role);
        foreach ($roles as $singleRole) {
            $singleRole = trim($singleRole); // Nettoyer les espaces
            if ($user->hasRole($singleRole)) {
                return $next($request);
            }
        }

        // Si aucun des rôles requis n'est présent
        $rolesString = implode(', ', array_map('ucfirst', $roles));
        abort(403, 'Accès non autorisé. Rôle requis: ' . $rolesString);
    }
}
