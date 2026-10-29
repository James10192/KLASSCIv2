<?php

namespace Tests\Feature\Bulletin;

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InscriptionBulletinRegenerationViewAvailabilityTest extends TestCase
{
    public function test_la_route_de_regeneration_est_enregistree_sur_presentation(): void
    {
        $this->assertTrue(Route::has('esbtp.bulletins.regenerer-inscription'));
    }

    public function test_la_vue_reste_rendable_si_le_cache_des_routes_est_obsolete(): void
    {
        $routesInitiales = Route::getRoutes();
        Route::setRoutes(new RouteCollection());

        try {
            $html = view('esbtp.etudiants.partials.bulletin-regeneration-actions', [
                'inscription' => new \stdClass(),
            ])->render();

            $this->assertSame('', trim($html));
        } finally {
            Route::setRoutes($routesInitiales);
        }
    }
}
