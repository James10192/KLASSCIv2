<?php

namespace Tests\Feature\Compta;

use App\Http\Controllers\ESBTPPaiementController;
use App\Http\Requests\Paiement\RejeterPaiementRequest;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class PaiementCriticalFlowRegressionTest extends TestCase
{
    public function test_critical_paiement_routes_are_registered_with_expected_permissions(): void
    {
        $expectations = [
            'esbtp.paiements.create' => ['permission:paiements.create'],
            'esbtp.paiements.store' => ['permission:paiements.create'],
            'esbtp.paiements.edit' => ['permission:paiements.edit'],
            'esbtp.paiements.update' => ['permission:paiements.edit'],
            'esbtp.paiements.show' => ['permission:paiements.view|paiements.view_own'],
            'esbtp.paiements.index' => ['permission:paiements.view|paiements.view_own'],
            'esbtp.paiements.valider' => ['permission:paiements.validate', 'throttle:60,1'],
            'esbtp.paiements.rejeter' => ['permission:paiements.validate', 'throttle:60,1'],
            'esbtp.paiements.valider-rapide' => ['permission:paiements.validate', 'throttle:60,1'],
            'esbtp.paiements.cancel-own' => ['throttle:30,1'],
        ];

        foreach ($expectations as $routeName => $middlewares) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route {$routeName} should be registered");

            foreach ($middlewares as $middleware) {
                $this->assertContains(
                    $middleware,
                    $route->gatherMiddleware(),
                    "Route {$routeName} should have middleware {$middleware}"
                );
            }
        }
    }

    public function test_reject_request_requires_explicit_reason_with_minimum_length(): void
    {
        $rules = (new RejeterPaiementRequest())->rules();

        $validator = Validator::make([
            'motif_rejet' => 'Court',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('motif_rejet', $validator->errors()->toArray());
    }

    public function test_reject_request_accepts_valid_reason_payload(): void
    {
        $rules = (new RejeterPaiementRequest())->rules();

        $validator = Validator::make([
            'motif_rejet' => 'Paiement duplique, merci de verifier la reference bancaire.',
        ], $rules);

        $this->assertFalse($validator->fails(), 'Payload should pass: '.$validator->errors()->first());
    }

    public function test_self_validation_guard_blocks_same_creator_and_validator(): void
    {
        $user = new User();
        $user->id = 77;
        $user->exists = true;
        $this->actingAs($user);

        $paiement = new ESBTPPaiement();
        $paiement->id = 10;
        $paiement->created_by = 77;
        $paiement->montant = 150000;

        $controller = app(ESBTPPaiementController::class);
        $method = new ReflectionMethod($controller, 'assertNotSelfValidation');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $paiement);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('propre paiement', $result['message']);
    }

    public function test_self_validation_guard_allows_different_validator(): void
    {
        $user = new User();
        $user->id = 78;
        $user->exists = true;
        $this->actingAs($user);

        $paiement = new ESBTPPaiement();
        $paiement->id = 11;
        $paiement->created_by = 77;
        $paiement->montant = 200000;

        $controller = app(ESBTPPaiementController::class);
        $method = new ReflectionMethod($controller, 'assertNotSelfValidation');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $paiement);

        $this->assertNull($result, 'Different user should be allowed to validate');
    }
}
