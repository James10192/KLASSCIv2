<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class LMDBulletinLiveResultsWiringTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = dirname(__DIR__, 3);
    }

    public function test_resultats_lmd_controller_uses_live_projection_and_current_year_fallback(): void
    {
        $controller = file_get_contents($this->root.'/app/Http/Controllers/ESBTPLMDResultatController.php');

        $this->assertStringContainsString('calculerProjectionsClasse', $controller);
        $this->assertStringContainsString("firstWhere('is_current', true)", $controller);
        $this->assertStringNotContainsString('?? $classe->annee_universitaire_id', $controller);
        $this->assertStringNotContainsString('ESBTPLMDBulletin::where', $controller);
    }

    public function test_lmd_bulk_generation_uses_year_scoped_created_student_cohort(): void
    {
        $service = file_get_contents($this->root.'/app/Services/LMDBulletinService.php');
        $controller = file_get_contents($this->root.'/app/Http/Controllers/ESBTPLMDBulletinController.php');

        $this->assertStringContainsString('studentIdsForGenerationCohort', $service);
        $this->assertStringContainsString("->where('annee_universitaire_id', \$anneeUniversitaireId)", $service);
        $this->assertStringContainsString("->where('workflow_step', 'etudiant_cree')", $service);
        $this->assertStringContainsString('studentIdsForGenerationCohort', $controller);
        $this->assertStringContainsString('isStudentInGenerationCohort', $controller);
        $this->assertStringContainsString('missing_active_registration', $controller);
        $this->assertStringNotContainsString("DB::table('esbtp_inscriptions')", $controller);
    }

    public function test_lmd_select_filters_students_by_academic_year_and_preflights_generation(): void
    {
        $select = file_get_contents($this->root.'/resources/views/esbtp/lmd/bulletins/select.blade.php');
        $preflightModal = file_get_contents($this->root.'/resources/views/esbtp/lmd/bulletins/partials/preflight-modal.blade.php');
        $classe = file_get_contents($this->root.'/resources/views/esbtp/lmd/resultats/classe.blade.php');
        $routes = file_get_contents($this->root.'/routes/web.php');

        $this->assertStringContainsString("params.set('annee_universitaire_id', this.anneeId)", $select);
        $this->assertStringContainsString('data-lmd-preflight', $select);
        $this->assertStringContainsString('data-lmd-preflight', $classe);
        $this->assertStringContainsString('entry.student_name', $preflightModal);
        $this->assertStringContainsString("name('bulletins.preflight')", $routes);
    }

    public function test_lmd_live_projection_preloads_class_cohort_data(): void
    {
        $projection = file_get_contents($this->root.'/app/Services/LMD/LmdBulletinProjectionService.php');

        $this->assertStringContainsString('whereIn(\'etudiant_id\', $studentIds)', $projection);
        $this->assertStringContainsString('->groupBy(\'etudiant_id\')', $projection);
        $this->assertStringContainsString('$notesByStudent', $projection);
        $this->assertStringNotContainsString('->map(fn (int $studentId): array => $this->calculerProjectionLive(', $projection);
    }
}
