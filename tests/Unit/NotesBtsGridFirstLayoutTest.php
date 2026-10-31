<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NotesBtsGridFirstLayoutTest extends TestCase
{
    public function test_coverage_is_kept_inside_a_collapsible_panel_not_above_the_grid(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/esbtp/notes/index.blade.php');
        $start = strpos($view, '<details class="nm-followup-panel"');
        $coverage = strpos($view, "@include('esbtp.partials._couverture-notes'");
        $grid = strpos($view, 'class="nm-grid-wrapper table-responsive notes-grid-wrapper"');
        $this->assertNotFalse($start);
        $this->assertNotFalse($coverage);
        $this->assertNotFalse($grid);
        $this->assertTrue($start < $coverage && $coverage < $grid);
        $this->assertSame(1, substr_count($view, "@include('esbtp.partials._couverture-notes'"));
        $this->assertStringContainsString('id="nm-followup-panel"', $view);
        $this->assertStringContainsString('removeAttribute(\'open\')', $view);
    }

    public function test_bts_ajax_dom_and_documents_are_not_replaced(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/esbtp/notes/index.blade.php');
        foreach (['matiereSelect', 'periodeFilter', 'nm-student-search', 'notesGrid',
            'studentsRows', 'nm-load-more-btn', 'nm-documents-panel', 'saveAllNotesBtn',
            'nmBlankOrientation', 'nmBlankColumns', 'previewBlankPdfBtn',
            'exportBlankPdfBtn', 'exportExcelBtn'] as $id) {
            $fragment = $id === 'nm-documents-panel' ? 'class="'.$id.'"' : 'id="'.$id.'"';
            $this->assertSame(1, substr_count($view, $fragment), $id);
        }
        $this->assertStringContainsString('<x-au-select id="matiereSelect"', $view);
        $this->assertStringContainsString('<x-au-select id="periodeFilter"', $view);
        $this->assertStringContainsString("@canany(['notes.feuille_vierge.export', 'notes.export_excel'])", $view);
        $this->assertStringNotContainsString('window.location.reload()', $view);
    }

    public function test_bts_grid_owns_scrolling_at_desktop_and_mobile_sizes(): void
    {
        $css = file_get_contents(__DIR__.'/../../public/css/notes-modal-bts-premium.css');
        $this->assertStringContainsString('.nm-followup-popover', $css);
        $this->assertStringContainsString('max-height: min(58dvh, 460px)', $css);
        $this->assertStringContainsString('overflow: hidden !important', $css);
        $this->assertStringContainsString('overflow: auto !important', $css);
        $this->assertStringContainsString('@media (max-width: 700px)', $css);
    }
}
