<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Models\AcademicActorAssignment;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetDocument;
use App\Policies\AcademicActorAssignmentPolicy;
use App\Policies\GradeSheetDocumentPolicy;
use App\Policies\GradeSheetPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AcademicPilotageRoutesTest extends TestCase
{
    public function test_pilotage_workspace_routes_are_registered_with_permissions(): void
    {
        $routes = [
            'esbtp.pilotage-academique.index' => 'permission:academic_pilotage.view',
            'esbtp.pilotage-academique.data' => 'permission:academic_pilotage.view',
            'esbtp.pilotage-academique.classes.show' => 'permission:academic_health.view',
            'esbtp.pilotage-academique.etudiants.show' => 'permission:academic_health.view',
        ];

        foreach ($routes as $name => $permission) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Missing route {$name}");
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware);
            $this->assertContains('permission:module.academic_pilotage.access', $middleware);
            $this->assertContains($permission, $middleware);
        }
    }

    public function test_grade_sheet_routes_are_protected_and_throttled(): void
    {
        $names = [
            'esbtp.academic-sheets.store',
            'esbtp.academic-sheets.transition',
            'esbtp.academic-sheets.sync-entries',
            'esbtp.academic-sheets.documents.upload',
            'esbtp.academic-sheets.documents.download',
            'esbtp.academic-assignments.store',
            'esbtp.academic-assignments.deactivate',
        ];

        foreach ($names as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Missing route {$name}");
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware);
            $this->assertContains('installed', $middleware);
            $this->assertContains('force.password.change', $middleware);
            $this->assertContains('paywall', $middleware);
            $this->assertContains(
                'permission:module.academic_pilotage.access',
                $middleware,
            );
            $this->assertNotEmpty(array_filter(
                $middleware,
                fn (string $item): bool => str_starts_with($item, 'throttle:'),
            ));
        }
    }

    public function test_document_download_requires_a_valid_signature(): void
    {
        $route = Route::getRoutes()->getByName(
            'esbtp.academic-sheets.documents.download'
        );

        $this->assertContains('signed', $route->gatherMiddleware());
    }

    public function test_grade_sheet_policies_are_registered(): void
    {
        $this->assertInstanceOf(GradeSheetPolicy::class, Gate::getPolicyFor(GradeSheet::class));
        $this->assertInstanceOf(
            GradeSheetDocumentPolicy::class,
            Gate::getPolicyFor(GradeSheetDocument::class),
        );
        $this->assertInstanceOf(
            AcademicActorAssignmentPolicy::class,
            Gate::getPolicyFor(AcademicActorAssignment::class),
        );
    }
}
