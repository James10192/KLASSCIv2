<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use Illuminate\Routing\Route;
use Tests\TestCase;

class DirecteurEtudesHttpAccessTest extends TestCase
{
    public function test_academic_route_groups_accept_direct_studies_without_opening_finance(): void
    {
        $notes = \Route::getRoutes()->getByName('esbtp.notes.index');
        $classes = \Route::getRoutes()->getByName('esbtp.classes.index');
        $jurys = \Route::getRoutes()->getByName('esbtp.lmd.jurys.index');
        $evaluations = \Route::getRoutes()->getByName('esbtp.evaluations.index');
        $planning = \Route::getRoutes()->getByName('esbtp.planning-general.index');
        $settings = \Route::getRoutes()->getByName('esbtp.settings.index');
        $compta = \Route::getRoutes()->getByName('esbtp.comptabilite.dashboard');
        $frais = \Route::getRoutes()->getByName('esbtp.frais.index');

        foreach ([$notes, $classes, $jurys, $evaluations, $planning] as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $this->assertTrue(
                collect($route->gatherMiddleware())->contains(
                    fn ($middleware) => str_contains((string) $middleware, 'identity.direct_studies')
                ),
                $route->getName().' must accept identity.direct_studies'
            );
        }

        foreach ([$settings, $compta, $frais] as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $this->assertFalse(
                collect($route->gatherMiddleware())->contains(
                    fn ($middleware) => str_contains((string) $middleware, 'identity.direct_studies')
                ),
                $route->getName().' must stay closed to directeurEtudes'
            );
        }
    }

    public function test_dashboard_router_still_sends_direct_studies_before_coordinate(): void
    {
        $source = file_get_contents((new \ReflectionClass(DashboardController::class))->getFileName());
        $this->assertLessThan(
            strpos($source, "can('identity.coordinate')"),
            strpos($source, "can('identity.direct_studies')")
        );
    }
}
