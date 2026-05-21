<?php

namespace App\Http\Middleware;

use App\Helpers\InstallationHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlockInstallIfReady
{
    /**
     * Retourne 404 si l'application est déjà installée (superAdmin présent + DB OK + APP_INSTALLED=true).
     *
     * Sans ce middleware, un attaquant peut POST sur /install/database et réécrire le .env
     * (host/user/password DB) puis prendre le contrôle total du tenant.
     *
     * Voir audit sécurité 2026-05-21 — finding CRITICAL #1.
     */
    public function handle(Request $request, Closure $next)
    {
        if (InstallationHelper::isInstalled()) {
            throw new NotFoundHttpException();
        }

        return $next($request);
    }
}
