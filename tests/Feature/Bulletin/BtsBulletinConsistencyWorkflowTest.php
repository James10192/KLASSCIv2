<?php

namespace Tests\Feature\Bulletin;

use Tests\TestCase;

class BtsBulletinConsistencyWorkflowTest extends TestCase
{
    /** @test */
    public function bulletin_consistency_service_and_command_exist(): void
    {
        $service = file_get_contents(app_path('Services/ESBTP/BulletinConsistencyService.php'));
        $command = file_get_contents(app_path('Console/Commands/ResultatsBulletinConsistencyDiagnoseCommand.php'));

        $this->assertStringContainsString('class BulletinConsistencyService', $service);
        $this->assertStringContainsString('function getSnapshot', $service);
        $this->assertStringContainsString("resultats:bulletin-consistency-diagnose", $command);
    }

    /** @test */
    public function bulletin_consistency_routes_are_registered(): void
    {
        $webRoutes = file_get_contents(base_path('routes/web.php'));
        $apiRoutes = file_get_contents(base_path('routes/api.php'));

        $this->assertStringContainsString("name('esbtp.bulletins.check-consistency')", $webRoutes);
        $this->assertStringContainsString("name('esbtp.bulletins.regenerate')", $webRoutes);
        $this->assertStringContainsString("bulletinConsistencyDiagnose", $apiRoutes);
    }

    /** @test */
    public function bts_results_views_expose_consistency_banner_and_warning_actions(): void
    {
        $detailView = file_get_contents(resource_path('views/esbtp/resultats/etudiant.blade.php'));
        $classView = file_get_contents(resource_path('views/esbtp/resultats/classe.blade.php'));
        $actionsView = file_get_contents(resource_path('views/components/student-results/action-buttons.blade.php'));

        $this->assertStringContainsString('sr-bulletin-banner', $detailView);
        $this->assertStringContainsString('data-consistency-action="preview_pdf"', $detailView);
        $this->assertStringContainsString('srWarningRegenerateBtn', $detailView);
        $this->assertStringContainsString('À régénérer', $classView);
        $this->assertStringContainsString("route('esbtp.bulletins.check-consistency'", $actionsView);
    }
}
