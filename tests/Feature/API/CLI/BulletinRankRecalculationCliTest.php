<?php

namespace Tests\Feature\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Controllers\API\CLI\CLIBulletinController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use App\Services\ESBTP\BulletinAverageBackfillService;
use App\Services\ESBTP\BulletinBulkGenerationCliService;
use App\Services\ESBTP\BulletinRankRecalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Mockery;
use ReflectionProperty;
use Tests\TestCase;

class BulletinRankRecalculationCliTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_route_is_registered(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->uri() === 'api/cli/bulletins/recalculate-ranks')
            ->map(fn ($route) => $route->methods())
            ->first();

        $this->assertNotNull($routes);
        $this->assertContains('POST', $routes);
    }

    public function test_dry_run_requires_read_ability(): void
    {
        $response = $this->controller()->recalculateRanks(
            $this->requestWithAbilities([], [])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_apply_requires_write_ability(): void
    {
        $response = $this->controller()->recalculateRanks(
            $this->requestWithAbilities(['cli:read'], ['apply' => 1])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_dry_run_does_not_write_ranks(): void
    {
        $service = Mockery::mock(BulletinRankRecalculationService::class);
        $service->shouldReceive('recalculate')
            ->once()
            ->with(false, 6, 86, 'semestre2')
            ->andReturn([
                'mode' => 'DRY-RUN (aucune ecriture)',
                'annee_universitaire_id' => 6,
                'classe_id' => 86,
                'periodes' => ['semestre2'],
                'classes_scannees' => 1,
                'bulletins_lus' => 3,
                'rang_1_avant' => 3,
                'rang_1_apres' => 1,
                'rangs_changes' => 2,
                'echantillons' => [
                    ['id' => 1, 'moyenne' => 16.13, 'rang_actuel' => 1, 'rang_propose' => 1],
                    ['id' => 2, 'moyenne' => 12.0, 'rang_actuel' => 1, 'rang_propose' => 2],
                    ['id' => 3, 'moyenne' => 7.24, 'rang_actuel' => 1, 'rang_propose' => 3],
                ],
            ]);

        $response = $this->controller($service)->recalculateRanks(
            $this->requestWithAbilities(['cli:read'], [
                'annee_universitaire_id' => 6,
                'classe_id' => 86,
                'periode' => 'semestre2',
            ])
        );

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true)['data'];
        $this->assertSame('DRY-RUN (aucune ecriture)', $payload['mode']);
        $this->assertSame(3, $payload['rang_1_avant']);
        $this->assertSame(1, $payload['rang_1_apres']);
        $this->assertSame([1, 2, 3], array_column($payload['echantillons'], 'rang_propose'));
    }

    public function test_apply_asks_service_to_write_ranks(): void
    {
        $service = Mockery::mock(BulletinRankRecalculationService::class);
        $service->shouldReceive('recalculate')
            ->once()
            ->with(true, 6, 86, 'semestre2')
            ->andReturn([
                'mode' => 'APPLIQUE',
                'annee_universitaire_id' => 6,
                'classe_id' => 86,
                'periodes' => ['semestre2'],
                'classes_scannees' => 1,
                'bulletins_lus' => 3,
                'rang_1_avant' => 3,
                'rang_1_apres' => 1,
                'rangs_changes' => 2,
                'echantillons' => [],
            ]);

        $response = $this->controller($service)->recalculateRanks(
            $this->requestWithAbilities(['cli:write'], [
                'apply' => 1,
                'annee_universitaire_id' => 6,
                'classe_id' => 86,
                'periode' => 'semestre2',
            ])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('APPLIQUE', $response->getData(true)['data']['mode']);
    }

    private function controller(?BulletinRankRecalculationService $service = null): CLIBulletinController
    {
        $controller = new CLIBulletinController(
            $service ?? Mockery::mock(BulletinRankRecalculationService::class),
            Mockery::mock(BulletinBulkGenerationCliService::class),
            Mockery::mock(BulletinAverageBackfillService::class)
        );
        $annee = new ESBTPAnneeUniversitaire([
            'name' => '2025-2026',
            'is_current' => true,
        ]);
        $annee->id = 6;
        $property = new ReflectionProperty(BaseApiController::class, 'anneeCouraante');
        $property->setAccessible(true);
        $property->setValue($controller, $annee);

        return $controller;
    }

    private function requestWithAbilities(array $abilities, array $payload): Request
    {
        $user = new User();
        $user->id = 1;
        $request = Request::create('/api/cli/bulletins/recalculate-ranks', 'POST', $payload);
        $request->setUserResolver(fn () => new class($abilities) {
            public function __construct(private array $abilities)
            {
            }

            public function tokenCan(string $ability): bool
            {
                return in_array($ability, $this->abilities, true);
            }

            public function getRoleNames()
            {
                return collect(['superAdmin']);
            }

            public function can(string $permission): bool
            {
                return true;
            }
        });

        return $request;
    }
}
