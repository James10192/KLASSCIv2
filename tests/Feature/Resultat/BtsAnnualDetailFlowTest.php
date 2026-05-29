<?php

namespace Tests\Feature\Resultat;

use Tests\TestCase;

class BtsAnnualDetailFlowTest extends TestCase
{
    /** @test */
    public function resultat_filter_request_accepts_annuel_periode(): void
    {
        $source = file_get_contents(app_path('Http/Requests/Resultat/ResultatsFilterRequest.php'));

        $this->assertStringContainsString(
            "'periode' => 'nullable|in:1,2,semestre1,semestre2,annuel'",
            $source
        );
    }

    /** @test */
    public function student_result_controller_contains_annual_detail_state_and_requested_class_priority(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/ESBTPResultatController.php'));

        $this->assertStringContainsString(
            "\$requestedClasseId = \$request->filled('classe_id')",
            $source
        );
        $this->assertStringContainsString(
            "\$classe_id = \$requestedClasseId ?? \$inscription?->classe_id;",
            $source
        );
        $this->assertStringContainsString(
            "private function normalizeBtsPeriode(?string \$rawPeriode): array",
            $source
        );
        $this->assertStringContainsString(
            "private function buildAnnualDetailUiState(string \$periode",
            $source
        );
    }

    /** @test */
    public function annual_list_and_detail_views_expose_the_expected_bts_ui_cues(): void
    {
        $detailView = file_get_contents(resource_path('views/esbtp/resultats/etudiant.blade.php'));
        $tableView = file_get_contents(resource_path('views/esbtp/resultats/partials/liste-etudiants.blade.php'));
        $rowsView = file_get_contents(resource_path('views/esbtp/resultats/partials/lignes-etudiants.blade.php'));

        $this->assertStringContainsString("data-periode=\"annuel\"", $detailView);
        $this->assertStringContainsString("\$bulletinWorkflowPeriode", $detailView);
        $this->assertStringContainsString("Provisoire", $tableView);
        $this->assertStringContainsString("\$detail_periode ?? 'annuel'", $tableView);
        $this->assertStringContainsString("\$detail_periode ?? 'annuel'", $rowsView);
    }
}
