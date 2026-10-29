<?php

namespace Tests\Feature\Notes;

use Tests\TestCase;

class NotesAcademicYearAjaxContractTest extends TestCase
{
    public function test_bts_changes_year_without_native_form_submission(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/notes/index.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPNoteController.php'));

        $this->assertStringNotContainsString('filtersForm.submit()', $view);
        $this->assertStringContainsString("formData.set('classes_ajax', '1')", $view);
        $this->assertStringContainsString('nmAcademicYearId = selectedYear', $view);
        $this->assertStringContainsString("pdfUrl.searchParams.set('annee_universitaire_id'", $view);
        $this->assertStringContainsString("'hero_stats' => \$heroStats", $controller);
    }

    public function test_lmd_changes_year_via_ajax_and_updates_forms_and_coverage(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/lmd/notes/index.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPLMDNoteController.php'));
        $suivi = file_get_contents(resource_path('views/esbtp/lmd/notes/partials/_suivi-script.blade.php'));

        $this->assertStringNotContainsString('onchange="this.form.submit()"', $view);
        $this->assertStringContainsString("url.searchParams.set('classes_ajax', '1')", $view);
        $this->assertStringContainsString('lmdAcademicYearId = newYear', $view);
        $this->assertStringContainsString('anneeSuivi = newYear', $view);
        $this->assertStringContainsString("view('esbtp.lmd.notes.partials._classes'", $controller);
        $this->assertStringContainsString('let anneeSuivi = lmdAcademicYearId', $suivi);
    }
}
