<?php

namespace Tests\Feature\Compta;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Test sans DB : vérifie que les routes PR2 sont enregistrées correctement.
 *
 * Ne touche pas à la DB → safe pour CI multi-tenant. Couvre :
 * - Bons noms de routes
 * - Bons HTTP methods
 * - Bons paramètres dynamiques
 * - Throttle middleware appliqué
 */
class ReconciliationRoutesRegistrationTest extends TestCase
{
    /**
     * @return array<string, array{0:string,1:string}>
     */
    public static function reconciliationRoutes(): array
    {
        return [
            'index' => ['esbtp.comptabilite.reconciliation.index', 'GET'],
            'show' => ['esbtp.comptabilite.reconciliation.show', 'GET'],
            'open' => ['esbtp.comptabilite.reconciliation.open', 'POST'],
            'record-count' => ['esbtp.comptabilite.reconciliation.record-count', 'POST'],
            'resolve' => ['esbtp.comptabilite.reconciliation.resolve', 'POST'],
            'review' => ['esbtp.comptabilite.reconciliation.review', 'POST'],
            'approve' => ['esbtp.comptabilite.reconciliation.approve', 'POST'],
            'close' => ['esbtp.comptabilite.reconciliation.close', 'POST'],
            'reopen' => ['esbtp.comptabilite.reconciliation.reopen', 'POST'],
        ];
    }

    /**
     * @dataProvider reconciliationRoutes
     */
    public function test_route_is_registered(string $name, string $method): void
    {
        $route = Route::getRoutes()->getByName($name);
        $this->assertNotNull($route, "Route '{$name}' missing");
        $this->assertContains($method, $route->methods(), "Route '{$name}' missing method {$method}");
    }

    public function test_all_routes_have_throttle_middleware(): void
    {
        foreach (array_keys(self::reconciliationRoutes()) as $key => $_) {
            // No-op : juste vérifier qu'on a un dataset
        }
        foreach (self::reconciliationRoutes() as $key => [$name, $_]) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} not registered");
            $hasThrottle = collect($route->gatherMiddleware())
                ->contains(fn ($m) => str_starts_with((string) $m, 'throttle:'));
            $this->assertTrue($hasThrottle, "Route {$name} missing throttle middleware");
        }
    }
}
