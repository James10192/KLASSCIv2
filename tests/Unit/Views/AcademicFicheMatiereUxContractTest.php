<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

class AcademicFicheMatiereUxContractTest extends TestCase
{
    private function view(string $name): string
    {
        $contents = file_get_contents(__DIR__.'/../../../resources/views/'.$name);
        $this->assertNotFalse($contents);

        return $contents;
    }

    public function test_subject_is_primary_in_the_grade_table_and_note_dialog(): void
    {
        $content = $this->view('esbtp/partials/fiche-notes-contenu.blade.php');
        $module = $this->view('esbtp/partials/fiche-notes-module.blade.php');
        $views = $this->view('esbtp/partials/_fiche-notes-view-script.blade.php');

        $this->assertStringContainsString('data-fng-matiere="{{ $eval->matiere?->name', $content);
        $this->assertStringContainsString('data-fng-matiere-id="{{ $eval->matiere_id', $content);
        $this->assertStringContainsString('class="fng-row-matiere"', $content);
        $this->assertStringContainsString('class="fng-row-evaluation"', $content);
        $this->assertLessThan(
            strpos($content, 'class="fng-row-evaluation"'),
            strpos($content, 'class="fng-row-matiere"')
        );
        $this->assertStringContainsString("modal('Note · '+(btn.dataset.matiere || btn.dataset.title)", $module);
        $this->assertStringContainsString("'<p class=\"fng-eval-context\">'+escape(btn.dataset.title)", $module);
    }

    public function test_grouped_semester_and_list_modes_are_accessible_without_duplicate_rows(): void
    {
        $content = $this->view('esbtp/partials/fiche-notes-contenu.blade.php');
        $module = $this->view('esbtp/partials/fiche-notes-module.blade.php');
        $views = $this->view('esbtp/partials/_fiche-notes-view-script.blade.php');

        $this->assertSame(1, substr_count($content, 'data-fng-eval-row'));
        $this->assertStringContainsString('data-fng-view="matiere" aria-pressed="true"', $content);
        $this->assertStringContainsString('data-fng-view="periode" aria-pressed="false"', $content);
        $this->assertStringContainsString('data-fng-view="liste" aria-pressed="false"', $content);
        $this->assertStringContainsString('data-fng-search', $content);
        $this->assertStringContainsString('data-fng-order="{{ $loop->index }}"', $content);
        $this->assertStringContainsString('data-fng-periode="{{ $eval->periode }}"', $content);
        $this->assertStringContainsString("state.mode==='matiere' || state.mode==='periode'", $views);
        $this->assertStringContainsString("const state=fngViewStates.get(id) || {mode:'matiere',search:'',collapsed:new Set(),searchCollapsed:new Set()}", $views);
        $this->assertStringContainsString("button.setAttribute('aria-expanded',String(expanded))", $views);
        $this->assertStringContainsString('button.dataset.fngGroupToggle=group.key', $views);
        $this->assertStringContainsString('tbody.replaceChildren(fragment)', $views);
        $this->assertStringContainsString('const collapsedSet=state.search.trim() ? state.searchCollapsed : state.collapsed', $views);
        $this->assertStringContainsString('state.searchCollapsed.clear()', $views);
    }

    public function test_ajax_note_mutations_preserve_mode_filters_permissions_and_endpoints(): void
    {
        $content = $this->view('esbtp/partials/fiche-notes-contenu.blade.php');
        $module = $this->view('esbtp/partials/fiche-notes-module.blade.php');
        $views = $this->view('esbtp/partials/_fiche-notes-view-script.blade.php');

        $this->assertStringContainsString("panel.querySelectorAll('[data-fng-inscription]').forEach(renderFngEvaluations)", $views);
        $this->assertStringContainsString('initializeFngEvaluations();', $module);
        $this->assertStringContainsString("esbtp.partials._fiche-notes-view-script", $module);
        $this->assertStringContainsString("const fngViewStates = new Map()", $views);
        $this->assertStringContainsString("normalize('NFD')", $views);
        $this->assertStringContainsString("panel.addEventListener('input'", $views);
        $this->assertStringContainsString("const btn=e.target.closest('button')", $module);
        $this->assertStringContainsString("data-save=\"{{ route('esbtp.notes.save-ajax') }}\"", $content);
        $this->assertStringContainsString("auth()->user()->can('notes.edit')", $content);
        $this->assertStringContainsString("auth()->user()->can('notes.create')", $content);
        $this->assertStringContainsString("data-fng-publish", $content);
        $this->assertStringContainsString("data-fng-delete", $content);
        $this->assertStringContainsString("data-fng-document", $content);
    }
}
