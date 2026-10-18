<?php

namespace Tests\Feature\Security;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EvaluationPdfRouteAccessTest extends TestCase
{
    public function test_legacy_pdf_alias_enforces_canonical_academic_permissions(): void
    {
        $canonical = Route::getRoutes()->getByName('esbtp.evaluations.pdf');
        $legacy = Route::getRoutes()->getByName('evaluations.pdf');

        $this->assertInstanceOf(RoutingRoute::class, $canonical);
        $this->assertInstanceOf(RoutingRoute::class, $legacy);
        $this->assertSame(['GET', 'HEAD'], $legacy->methods());

        foreach ($canonical->gatherMiddleware() as $middleware) {
            if ($middleware === 'auth' || str_starts_with($middleware, 'permission:')) {
                $this->assertContains(
                    $middleware,
                    $legacy->gatherMiddleware(),
                    'L\'alias PDF doit garder la protection '. $middleware
                );
            }
        }

        $this->assertContains('throttle:10,1', $legacy->gatherMiddleware());
        $this->assertSame('[0-9]+', $legacy->wheres['evaluation'] ?? null);
    }
}
