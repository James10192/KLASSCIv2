<?php

namespace App\Domain\Support\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * De quel module KLASSCI releve une page ?
 *
 * Lu d'abord sur la route elle-meme : un middleware `permission:module.X.access`
 * est la seule declaration fiable, et c'est celle qui decide deja de l'acces
 * (meme lecture que PorteDeRoute). A defaut, un prefixe de nom de route
 * (config/support.php) donne une reponse approximative. Sinon : null, pas
 * une supposition.
 */
final class ModuleDeRoute
{
    public static function pour(?string $nomDeRoute): ?string
    {
        if ($nomDeRoute === null || $nomDeRoute === '') {
            return null;
        }

        $route = Route::getRoutes()->getByName($nomDeRoute);
        if ($route !== null) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (is_string($middleware) && preg_match('/^(?:permission|can):(.*)$/', $middleware, $m)
                    && preg_match('/module\.([a-z_]+)\.access/', $m[1], $module)) {
                    return $module[1];
                }
            }
        }

        foreach (config('support.modules_par_prefixe', []) as $prefixe => $module) {
            if (Str::startsWith($nomDeRoute, $prefixe)) {
                return $module;
            }
        }

        return null;
    }
}
