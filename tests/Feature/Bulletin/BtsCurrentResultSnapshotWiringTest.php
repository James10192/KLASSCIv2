<?php

namespace Tests\Feature\Bulletin;

use Tests\TestCase;

class BtsCurrentResultSnapshotWiringTest extends TestCase
{
    /** @test */
    public function bts_results_now_reference_the_canonical_snapshot_service(): void
    {
        $snapshotService = file_get_contents(app_path('Services/ESBTP/BtsCurrentResultSnapshotService.php'));
        $resultatsController = file_get_contents(app_path('Http/Controllers/ESBTPResultatController.php'));
        $cliController = file_get_contents(app_path('Http/Controllers/API/CLI/CLIResultatController.php'));

        $this->assertStringContainsString('class BtsCurrentResultSnapshotService', $snapshotService);
        $this->assertStringContainsString('function getAnnualSnapshot', $snapshotService);
        $this->assertStringContainsString('currentResultSnapshotService->getAnnualSnapshot', $resultatsController);
        $this->assertStringContainsString('currentResultSnapshotService->getSemesterSnapshot', $resultatsController);
        $this->assertStringContainsString('currentResultSnapshotService->getAnnualSnapshot', $cliController);
    }
}
