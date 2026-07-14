<?php

namespace Tests\Feature\API\CLI;

use App\Domain\AcademicPilotage\Services\AcademicPilotageBackfillService;
use App\Http\Controllers\API\CLI\CLIAcademicPilotageController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AcademicPilotageCliTest extends TestCase
{
    /** @test */
    public function cli_routes_are_registered_with_expected_http_methods(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/cli/academic-pilotage/'))
            ->mapWithKeys(fn ($route) => [$route->uri() => $route->methods()]);

        $this->assertContains('GET', $routes['api/cli/academic-pilotage/diagnose']);
        $this->assertContains('POST', $routes['api/cli/academic-pilotage/backfill']);
        $this->assertContains('POST', $routes['api/cli/academic-pilotage/refresh']);
    }

    /** @test */
    public function backfill_requires_cli_admin_ability(): void
    {
        $request = $this->requestWithAbilities([], ['dry_run' => true]);

        $response = app(CLIAcademicPilotageController::class)->backfill(
            $request,
            app(AcademicPilotageBackfillService::class),
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    /** @test */
    public function real_backfill_requires_explicit_confirmation(): void
    {
        $request = $this->requestWithAbilities(['cli:admin'], [
            'dry_run' => false,
            'confirm' => false,
        ]);

        $response = app(CLIAcademicPilotageController::class)->backfill(
            $request,
            app(AcademicPilotageBackfillService::class),
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('confirm=true', $response->getData(true)['message']);
    }

    /** @test */
    public function diagnose_requires_cli_read_ability(): void
    {
        $response = app(CLIAcademicPilotageController::class)->diagnose(
            $this->requestWithAbilities([], []),
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    /** @test */
    public function refresh_requires_cli_admin_ability(): void
    {
        $response = app(CLIAcademicPilotageController::class)->refresh(
            $this->requestWithAbilities([], []),
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    private function requestWithAbilities(array $abilities, array $payload): Request
    {
        $user = new User();
        $user->id = 1;
        $request = Request::create('/', 'POST', $payload);
        $request->setUserResolver(fn () => new class($user, $abilities) extends User {
            public function __construct(User $user, private array $abilities)
            {
                parent::__construct();
                $this->setRawAttributes($user->getAttributes());
            }

            public function tokenCan(string $ability): bool
            {
                return in_array($ability, $this->abilities, true);
            }
        });

        return $request;
    }
}
