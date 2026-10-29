<?php

namespace App\Http\Middleware;

use App\Helpers\InstallationHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockInstallIfReady
{
    /**
     * Bloque l'accès aux routes /install/* quand l'app est déjà installée
     * (superAdmin présent + DB OK + APP_INSTALLED=true).
     *
     * Sans ce middleware, un attaquant peut POST sur /install/database et réécrire le .env
     * (host/user/password DB) puis prendre le contrôle total du tenant. Audit 2026-05-21.
     *
     * Comportement (normal Laravel) :
     *   - User authentifié → redirect vers /dashboard
     *   - User anonyme    → redirect vers /login
     *
     * Pour un NOUVEAU tenant (DB vide, pas de superAdmin, pas d'APP_INSTALLED dans .env),
     * isInstalled() retourne false et le flow install passe normalement (database →
     * migration → admin → finalize).
     */
    public function handle(Request $request, Closure $next)
    {
        if (InstallationHelper::isInstalled()) {
            if (Auth::check()) {
                return redirect()->route('dashboard');
            }
            return redirect()->route('login');
        }

        return $next($request);
    }
}
