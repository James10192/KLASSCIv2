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
                $this->assertTrue(
                    $this->routePorteLaGarde($route->gatherMiddleware(), $middleware),
                    "Route {$routeName} should have middleware {$middleware}"
                );
            }
        }
    }

    /**
     * La route porte-t-elle bien cette garde ?
     *
     * L'egalite stricte sur la chaine de middleware etait trop rigide. Le jour
     * ou une route accepte une permission ALTERNATIVE — c'est arrive avec
     * `permission:paiements.create|paiements.create.mobile_money`, ajoutee pour
     * l'encaissement mobile money — le test tombait alors que la route etait
     * toujours protegee, et il annoncait une faille de securite la ou il n'y
     * avait qu'un elargissement legitime.
     *
     * On verifie donc ce qui compte : qu'une garde `permission:` couvre bien la
     * permission attendue, seule ou parmi des alternatives. Un middleware qui
     * DISPARAIT fait toujours echouer le test, ce qui reste tout son objet.
     *
     * Les gardes qui ne sont pas des permissions (`throttle:60,1`) gardent la
     * comparaison exacte : la valeur y fait partie de la garantie.
     *
     * @param  array<int, string>  $middlewaresDeLaRoute
     */
    private function routePorteLaGarde(array $middlewaresDeLaRoute, string $attendu): bool
    {
        if (! str_starts_with($attendu, 'permission:')) {
            return in_array($attendu, $middlewaresDeLaRoute, true);
        }

        $permissionsAttendues = explode('|', substr($attendu, strlen('permission:')));

        foreach ($middlewaresDeLaRoute as $present) {
            if (! str_starts_with($present, 'permission:')) {
                continue;
            }

            $permissionsPresentes = explode('|', substr($present, strlen('permission:')));

            if (array_diff($permissionsAttendues, $permissionsPresentes) === []) {
                return true;
            }
        }

        return false;
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
