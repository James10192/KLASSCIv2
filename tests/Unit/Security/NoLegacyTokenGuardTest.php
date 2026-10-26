<?php

namespace Tests\Unit\Security;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * GHSA-f9x3-7vq9-cph6 : le TokenGuard historique de Laravel 9 n'est
 * pas corrigé dans la dépendance. Aucun point d'entrée ne doit l'activer.
 * Cette assertion réduit l'exposition ; elle NE remplace PAS la mise à jour
 * du framework et ne doit jamais servir à ignorer Composer audit.
 */
final class NoLegacyTokenGuardTest extends TestCase
{
    public function test_aucun_garde_authentification_n_utilise_driver_token(): void
    {
        foreach ((array) config('auth.guards', []) as $name => $guard) {
            $this->assertNotSame(
                'token',
                $guard['driver'] ?? null,
                "Garde $name utilise TokenGuard vulnérable, le retirer immédiatement."
            );
        }
    }

    public function test_aucune_route_n_utilise_un_auth_api_legacy(): void
    {
        foreach (Route::getRoutes()->getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                $this->assertNotSame('auth:api', $middleware, $route->uri());
            }
        }
    }
}
