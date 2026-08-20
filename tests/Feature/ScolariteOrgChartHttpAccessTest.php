<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResponsableScolariteDashboardController;
use App\Http\Controllers\ServiceScolariteDashboardController;
use App\Services\PermissionRegistry;
use Illuminate\Routing\Route;
use Tests\TestCase;

class ScolariteOrgChartHttpAccessTest extends TestCase
{
    public function test_dashboard_router_checks_registrar_before_school_manager(): void
    {
        $source = file_get_contents((new \ReflectionClass(DashboardController::class))->getFileName());

        $this->assertLessThan(
            strpos($source, "can('identity.school_manager')"),
            strpos($source, "can('identity.registrar')")
        );
        $this->assertLessThan(
            strpos($source, "can('identity.school_manager')"),
            strpos($source, "can('identity.registrar_clerk')")
        );
        $this->assertLessThan(
            strpos($source, "can('identity.registrar_clerk')"),
            strpos($source, "can('identity.registrar')")
        );
        $this->assertStringContainsString("route('dashboard.responsable-scolarite')", $source);
        $this->assertStringContainsString("route('dashboard.service-scolarite')", $source);
    }

    public function test_dedicated_scolarite_dashboards_exist_and_are_gated(): void
    {
        $responsable = \Route::getRoutes()->getByName('dashboard.responsable-scolarite');
        $service = \Route::getRoutes()->getByName('dashboard.service-scolarite');

        $this->assertInstanceOf(Route::class, $responsable);
        $this->assertInstanceOf(Route::class, $service);
        $this->assertSame(ResponsableScolariteDashboardController::class.'@index', $responsable->getActionName());
        $this->assertSame(ServiceScolariteDashboardController::class.'@index', $service->getActionName());
        $this->assertTrue(
            collect($responsable->gatherMiddleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'identity.registrar'))
        );
        $this->assertTrue(
            collect($service->gatherMiddleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'identity.registrar_clerk'))
        );
    }

    public function test_academic_screens_accept_scolarite_identities_without_opening_finance(): void
    {
        $notes = \Route::getRoutes()->getByName('esbtp.notes.index');
        $etudiants = \Route::getRoutes()->getByName('esbtp.etudiants.index');
        $settings = \Route::getRoutes()->getByName('esbtp.settings.index');
        $compta = \Route::getRoutes()->getByName('esbtp.comptabilite.dashboard');

        foreach ([$notes, $etudiants] as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $middleware = collect($route->gatherMiddleware());
            $this->assertTrue($middleware->contains(fn ($item) => str_contains((string) $item, 'identity.registrar')));
            $this->assertTrue($middleware->contains(fn ($item) => str_contains((string) $item, 'identity.registrar_clerk')));
        }

        foreach ([$settings, $compta] as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $middleware = collect($route->gatherMiddleware());
            $this->assertFalse($middleware->contains(fn ($item) => str_contains((string) $item, 'identity.registrar')));
            $this->assertFalse($middleware->contains(fn ($item) => str_contains((string) $item, 'identity.registrar_clerk')));
        }
    }

    public function test_mobile_money_create_route_is_open_to_comptable_permission(): void
    {
        $create = \Route::getRoutes()->getByName('esbtp.paiements.create');
        $this->assertInstanceOf(Route::class, $create);
        $this->assertTrue(
            collect($create->gatherMiddleware())->contains(
                fn ($middleware) => str_contains((string) $middleware, 'paiements.create.mobile_money')
            )
        );
    }

    public function test_registry_keeps_scolarite_roles_without_finance(): void
    {
        $registry = new PermissionRegistry();

        foreach (['responsableScolarite', 'serviceScolarite', 'directeurEtudes'] as $role) {
            $defaults = $registry->defaultPermissionsFor($role);
            $this->assertNotContains('paiements.view', $defaults, $role);
            $this->assertNotContains('system.manage', $defaults, $role);
            $this->assertNotContains('admin.access', $defaults, $role);
            $this->assertNotContains('*', $defaults, $role);
        }
    }

    public function test_dashboard_router_checks_enrollment_officer_before_school_manager(): void
    {
        $source = file_get_contents((new \ReflectionClass(DashboardController::class))->getFileName());

        $this->assertLessThan(
            strpos($source, "can('identity.school_manager')"),
            strpos($source, "can('identity.enrollment_officer')")
        );
        $this->assertGreaterThan(
            strpos($source, "can('identity.registrar_clerk')"),
            strpos($source, "can('identity.enrollment_officer')")
        );
        $this->assertStringContainsString("route('dashboard.agent-inscription')", $source);
    }

    public function test_agent_inscription_dashboard_is_gated(): void
    {
        $route = \Route::getRoutes()->getByName('dashboard.agent-inscription');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(\App\Http\Controllers\AgentInscriptionDashboardController::class.'@index', $route->getActionName());
        $this->assertTrue(
            collect($route->gatherMiddleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'identity.enrollment_officer'))
        );
    }

    public function test_agent_can_reach_inscriptions_but_not_finance_or_settings(): void
    {
        $etudiants = \Route::getRoutes()->getByName('esbtp.etudiants.index');
        $inscriptions = \Route::getRoutes()->getByName('esbtp.inscriptions.index');
        $reinscriptions = \Route::getRoutes()->getByName('esbtp.reinscription.index');
        $settings = \Route::getRoutes()->getByName('esbtp.settings.index');
        $compta = \Route::getRoutes()->getByName('esbtp.comptabilite.dashboard');
        $notes = \Route::getRoutes()->getByName('esbtp.notes.index');

        foreach ([$etudiants, $inscriptions, $reinscriptions] as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $this->assertTrue(
                collect($route->gatherMiddleware())->contains(fn ($item) => str_contains((string) $item, 'identity.enrollment_officer'))
            );
        }

        foreach ([$settings, $compta, $notes] as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $this->assertFalse(
                collect($route->gatherMiddleware())->contains(fn ($item) => str_contains((string) $item, 'identity.enrollment_officer'))
            );
        }
    }

    public function test_registry_keeps_agent_without_finance(): void
    {
        $registry = new PermissionRegistry();
        $defaults = $registry->defaultPermissionsFor('agentInscription');

        $this->assertNotContains('paiements.view', $defaults);
        $this->assertNotContains('system.manage', $defaults);
        $this->assertNotContains('admin.access', $defaults);
        $this->assertNotContains('*', $defaults);
    }
}
