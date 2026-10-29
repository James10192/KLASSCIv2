<?php

namespace Tests\Feature\LMD;

use Tests\TestCase;

/**
 * Tests Feature pour la vue calendrier des examens :
 *  - route /feed JSON (FullCalendar)
 *  - tabs Liste/Calendrier dans la vue index
 *  - permission lmd.examens.view + throttle
 *
 * Pas de DB nécessaire — TestCase pur.
 */
class ExamensCalendrierTest extends TestCase
{
    public function test_feed_route_exists_and_named(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.examens.feed');
        $this->assertNotNull($route, 'Route esbtp.examens.feed doit exister');
        $this->assertContains('GET', $route->methods());
        $this->assertStringContainsString('/feed', $route->uri());
    }

    public function test_feed_route_requires_permission_view_and_has_throttle(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.examens.feed');
        $mw = $route->gatherMiddleware();
        $this->assertTrue(collect($mw)->contains(fn ($m) => str_contains((string) $m, 'lmd.examens.view')));
        $this->assertTrue(collect($mw)->contains(fn ($m) => str_contains((string) $m, 'throttle')));
    }

    public function test_feed_requires_auth(): void
    {
        $response = $this->get('/esbtp/examens/feed');
        $this->assertContains($response->status(), [302, 401, 403, 419]);
    }

    public function test_index_view_includes_calendrier_tabs(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/examens/index.blade.php'));
        $this->assertStringContainsString('exp-view-tabs', $content);
        $this->assertStringContainsString('exp-view-tab--active', $content);
        $this->assertStringContainsString("view === 'calendrier'", $content);
        $this->assertStringContainsString("view === 'liste'", $content);
    }

    public function test_index_view_includes_fullcalendar_lib(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/examens/index.blade.php'));
        $this->assertStringContainsString('fullcalendar@5', $content);
        $this->assertStringContainsString("locales/fr.js", $content, 'FullCalendar locale FR doit être chargé');
    }

    public function test_index_view_includes_calendar_legend(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/examens/index.blade.php'));
        $this->assertStringContainsString('exp-calendar-legend', $content);
        $this->assertStringContainsString('Examen terminal', $content);
        $this->assertStringContainsString('Rattrapage', $content);
        $this->assertStringContainsString('Soutenance', $content);
    }

    public function test_controller_has_calendar_feed_method(): void
    {
        $controller = new \App\Http\Controllers\ESBTPExamenPlanifieController(
            app(\App\Services\ExamenSchedulingService::class)
        );
        $this->assertTrue(method_exists($controller, 'calendarFeed'));
    }
}
