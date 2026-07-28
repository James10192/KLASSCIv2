<?php

namespace Tests\Feature\Bulletin;

use Tests\TestCase;

class BtsBulkBulletinGenerationContractTest extends TestCase
{
    public function test_bulk_generation_has_structured_json_preflight_and_recalculate_contract(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPBulletinController.php'));
        $request = file_get_contents(app_path('Http/Requests/Bulletin/GenerateClasseBulletinsRequest.php'));
        $routes = file_get_contents(base_path('routes/web.php'));
        $resultDto = file_get_contents(app_path('Domain/AcademicPilotage/DTO/BulkBulletinGenerationResult.php'));

        $this->assertStringContainsString('BtsBulkBulletinGenerationService', $controller);
        $this->assertStringContainsString('response()->json($result->toArray(), $result->statusCode())', $controller);
        $this->assertStringContainsString('preflightClasseBulletins', $controller);
        $this->assertStringContainsString("'recalculer'             => 'sometimes|boolean'", $request);
        $this->assertStringContainsString("'incomplete_reason'      => 'nullable|string|min:8|max:1000'", $request);
        $this->assertStringContainsString("name('esbtp.bulletins.generer-classe.preflight')", $routes);
        $this->assertStringContainsString("'created' => \$this->created", $resultDto);
        $this->assertStringContainsString("'blocking_errors' => \$this->blockingErrors", $resultDto);
        $this->assertStringContainsString('public function statusCode(): int', $resultDto);
    }

    public function test_bulk_generation_cohort_is_bound_to_active_year_inscriptions(): void
    {
        $service = file_get_contents(app_path('Domain/AcademicPilotage/Services/BtsBulkBulletinGenerationService.php'));

        $this->assertStringContainsString('private function activeStudentsForClass', $service);
        $this->assertStringContainsString('ESBTPInscription::query()', $service);
        $this->assertStringContainsString("->where('classe_id', \$classeId)", $service);
        $this->assertStringContainsString("->where('annee_universitaire_id', \$academicYearId)", $service);
        $this->assertStringContainsString("->where('status', 'active')", $service);
        $this->assertStringContainsString("->where('workflow_step', 'etudiant_cree')", $service);
        $this->assertStringNotContainsString("ESBTPEtudiant::where('classe_id'", $service);
    }

    public function test_select_page_does_not_treat_redirect_or_failure_as_success(): void
    {
        $view = $this->selectPageSource();

        $this->assertStringContainsString("route('esbtp.bulletins.generer-classe.preflight')", $view);
        $this->assertStringContainsString('parseJsonResponse', $view);
        $this->assertStringContainsString('Le serveur a redirige la requete au lieu de retourner le resultat JSON.', $view);
        $this->assertStringContainsString('this.lastGeneration = data', $view);
        $this->assertStringContainsString('Aucun bulletin genere.', $view);
        $this->assertStringContainsString('generationStudentsLabel()', $view);
        $this->assertStringContainsString('this.preflight?.students_count', $view);
        $this->assertStringNotContainsString('Bulletins générés pour la classe. Redirection…', $view);
    }

    public function test_preview_and_select_accessibility_contracts_are_visible(): void
    {
        $selectView = $this->selectPageSource();
        $blockedView = file_get_contents(resource_path('views/esbtp/bulletins/preview-blocked.blade.php'));
        $component = file_get_contents(resource_path('views/components/au-select.blade.php'));

        $this->assertStringContainsString('previewIssue', $selectView);
        $this->assertStringContainsString('resolvePreviewUrl', $selectView);
        $this->assertStringContainsString('Ouvrir la configuration requise', $blockedView);
        $this->assertStringContainsString('aria-label="{{ $label ?: $placeholder }}"', $component);
        $this->assertStringContainsString('@keydown.arrow-down.prevent="openAndFocusNext()"', $component);
        $this->assertStringContainsString('@keydown.enter.prevent="open ? selectFocused() : openAndFocusNext()"', $component);
        $this->assertStringContainsString('role="combobox"', $component);
    }

    private function selectPageSource(): string
    {
        return file_get_contents(resource_path('views/esbtp/bulletins/select.blade.php'))
            ."\n"
            .file_get_contents(resource_path('views/esbtp/bulletins/partials/select-scripts.blade.php'));
    }
}
