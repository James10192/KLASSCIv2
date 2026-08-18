<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirecteurEtudesDashboardController;
use App\Services\PermissionRegistry;
use Illuminate\Routing\Route;
use Tests\TestCase;

class DirecteurEtudesRoutingTest extends TestCase
{
    public function test_dashboard_router_checks_direct_studies_before_coordinate(): void
    {
        $source = file_get_contents((new \ReflectionClass(DashboardController::class))->getFileName());
        $directStudies = strpos($source, "can('identity.direct_studies')");
        $coordinate = strpos($source, "can('identity.coordinate')");

        $this->assertNotFalse($directStudies);
        $this->assertNotFalse($coordinate);
        $this->assertLessThan($coordinate, $directStudies);
        $this->assertStringContainsString("route('dashboard.directeur-etudes')", $source);
    }

    public function test_dedicated_dashboard_routes_exist_and_are_gated(): void
    {
        $index = \Route::getRoutes()->getByName('dashboard.directeur-etudes');
        $data = \Route::getRoutes()->getByName('dashboard.directeur-etudes.data');

        $this->assertInstanceOf(Route::class, $index);
        $this->assertInstanceOf(Route::class, $data);
        $this->assertSame(DirecteurEtudesDashboardController::class.'@index', $index->getActionName());
        $this->assertTrue(
            collect($index->gatherMiddleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'identity.direct_studies'))
        );
    }

    public function test_personnel_and_settings_routes_stay_on_expected_gates(): void
    {
        $create = \Route::getRoutes()->getByName('esbtp.directeurs-etudes.create');
        $settings = \Route::getRoutes()->getByName('esbtp.settings.index');
        $compta = \Route::getRoutes()->getByName('esbtp.comptabilite.dashboard');
        $notes = \Route::getRoutes()->getByName('esbtp.notes.index');
        $jurys = \Route::getRoutes()->getByName('esbtp.lmd.jurys.index');
        $frais = \Route::getRoutes()->getByName('esbtp.frais.index');

        $this->assertNotNull($create);
        $this->assertNotNull($settings);
        $this->assertNotNull($compta);
        $this->assertTrue(
            collect($settings->gatherMiddleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'system.manage'))
        );
        $this->assertTrue(
            collect($notes->gatherMiddleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'identity.direct_studies'))
        );
        $this->assertTrue(
            collect($jurys->gatherMiddleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'identity.direct_studies'))
        );
        $this->assertFalse(
            collect($frais->gatherMiddleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'identity.direct_studies'))
        );
        $this->assertFalse(
            collect($compta->gatherMiddleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'identity.direct_studies'))
        );
    }

    public function test_registry_keeps_the_role_academic_only(): void
    {
        $defaults = (new PermissionRegistry())->defaultPermissionsFor('directeurEtudes');

        $this->assertNotContains('system.manage', $defaults);
        $this->assertNotContains('comptabilite.dashboard.view', $defaults);
        $this->assertContains('identity.direct_studies', $defaults);
    }
}
