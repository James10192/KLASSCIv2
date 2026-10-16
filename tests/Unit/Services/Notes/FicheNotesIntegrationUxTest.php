<?php

namespace Tests\Unit\Services\Notes;

use PHPUnit\Framework\TestCase;

final class FicheNotesIntegrationUxTest extends TestCase
{
    private function source(string $path): string
    {
        return (string) file_get_contents(dirname(__DIR__, 4).'/'.$path);
    }

    public function test_module_integrates_without_second_large_blue_hero(): void
    {
        $source = $this->source('resources/views/esbtp/partials/fiche-notes-module.blade.php');
        $this->assertStringContainsString('.fng-head{padding:9px 3px 13px;', $source);
        $this->assertStringNotContainsString('.fng-head{background:linear-gradient', $source);
        $this->assertStringContainsString('.fng-block{border:', $source);
        $this->assertStringContainsString('box-shadow:none', $source);
    }

    public function test_hidden_academic_tab_only_loads_when_visible(): void
    {
        $source = $this->source('resources/views/esbtp/partials/fiche-notes-module.blade.php');
        $this->assertStringContainsString("if ('IntersectionObserver' in window)", $source);
        $this->assertStringContainsString('observer.observe(panel)', $source);
        $this->assertStringContainsString('observer.disconnect()', $source);
        $this->assertStringNotContainsString('window.location.reload', $source);
        $this->assertStringContainsString("form.set('_method',method)", $source);
    }

    public function test_ajax_deletion_preserves_grade_sheet_and_exam_lock(): void
    {
        $source = $this->source('app/Http/Controllers/ESBTP/FicheNotesController.php');
        $this->assertStringContainsString('GradeSheetNoteMutationGuard::class', $source);
        $this->assertStringContainsString('assertEvaluationMutable((int) $evaluation->id, true)', $source);
        $this->assertStringContainsString("abort_unless(auth()->user()->can('notes.delete'), 403)", $source);
        $this->assertStringContainsString('$this->authorize(\'view\', $inscription)', $source);
    }

    public function test_both_existing_pages_use_same_permissioned_partial(): void
    {
        $student = $this->source('resources/views/esbtp/etudiants/show.blade.php');
        $enrollment = $this->source('resources/views/esbtp/inscriptions/show.blade.php');
        foreach ([$student, $enrollment] as $view) {
            $this->assertStringContainsString("@can('notes.view')", $view);
            $this->assertStringContainsString("@include('esbtp.partials.fiche-notes-module'", $view);
        }
    }
}
