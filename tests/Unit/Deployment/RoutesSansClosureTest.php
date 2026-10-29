<?php

namespace Tests\Unit\Deployment;

use Closure;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Le déploiement met les routes en cache (`route:cache`, voir
 * ReconstructionDesCaches). Une route dont l'action est une closure y est
 * sérialisée et signée avec APP_KEY : la clé changée sans vider le cache de
 * routes, chacune lève InvalidSignatureException. Une action de contrôleur,
 * `Route::view` ou `Route::redirect` n'ont pas ce défaut.
 *
 * C'est la raison pour laquelle adminKlassci n'appelait pas `route:cache`.
 * Ce test garde la condition qui rend ce choix inutile.
 */
class RoutesSansClosureTest extends TestCase
{
    public function test_aucune_route_n_a_une_closure_pour_action(): void
    {
        $fautes = [];

        foreach (Route::getRoutes() as $route) {
            if ($route->getAction('uses') instanceof Closure) {
                $fautes[] = implode('|', $route->methods()).' '.$route->uri();
            }
        }

        $this->assertSame([], $fautes, "Routes en closure, incompatibles avec route:cache :\n".implode("\n", $fautes));
    }
}
