<?php

namespace Tests\Feature\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Controllers\API\CLI\CLIBulletinController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Services\ESBTP\BulletinAverageBackfillService;
use App\Services\ESBTP\BulletinBulkGenerationCliService;
use App\Services\ESBTP\BulletinRankRecalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Mockery;
use ReflectionProperty;
use Tests\TestCase;

class BulletinBulkGenerationCliTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_route_is_registered(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->uri() === 'api/cli/bulletins/generate-missing')
            ->map(fn ($route) => $route->methods())
            ->first();

        $this->assertNotNull($routes);
        $this->assertContains('POST', $routes);
    }

    public function test_apply_requires_write_ability(): void
    {
        $response = $this->controller()->generateMissing(
            $this->requestWithAbilities(['cli:read'], [
                'apply' => 1,
                'classe_id' => 86,
                'periode' => 'semestre2',
            ])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_dry_run_does_not_generate(): void
    {
        $service = Mockery::mock(BulletinBulkGenerationCliService::class);
        $service->shouldReceive('generate')
            ->once()
            ->with(false, 6, 86, 'semestre2', false, null, Mockery::any())
            ->andReturn([
                'mode' => 'DRY-RUN (aucune ecriture)',
                'classe_id' => 86,
                'students_count' => 46,
                'generatable_count' => 45,
                'existing_count' => 1,
                'created' => 0,
                'regenerated' => 0,
            ]);

        $response = $this->controller($service)->generateMissing(
            $this->requestWithAbilities(['cli:read'], [
                'annee_universitaire_id' => 6,
                'classe_id' => 86,
                'periode' => 'semestre2',
            ])
        );

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true)['data'];
        $this->assertSame('DRY-RUN (aucune ecriture)', $payload['mode']);
        $this->assertSame(0, $payload['created']);
        $this->assertSame(45, $payload['generatable_count']);
    }

    private function controller(?BulletinBulkGenerationCliService $service = null): CLIBulletinController
    {
        $controller = new CLIBulletinController(
            Mockery::mock(BulletinRankRecalculationService::class),
            $service ?? Mockery::mock(BulletinBulkGenerationCliService::class),
            Mockery::mock(BulletinAverageBackfillService::class),
            Mockery::mock(\App\Services\ESBTP\BulletinSubjectRankBackfillService::class)
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
        $request = Request::create('/api/cli/bulletins/generate-missing', 'POST', $payload);
        $request->setUserResolver(fn () => new class($abilities) {
            public int $id = 1;

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

            public function can($permission, $arguments = [])
            {
                return true;
            }
        });

        return $request;
    }
}
