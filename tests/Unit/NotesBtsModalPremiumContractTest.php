<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NotesBtsModalPremiumContractTest extends TestCase
{
    public function test_bts_modal_preserves_ajax_ids_and_splits_documents_from_validation(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/esbtp/notes/index.blade.php');
        foreach (['matiereSelect', 'periodeFilter', 'notesGrid', 'studentsRows',
            'nmBlankOrientation', 'nmBlankColumns', 'previewBlankPdfBtn',
            'exportBlankPdfBtn', 'exportExcelBtn', 'saveAllNotesBtn'] as $id) {
            $this->assertSame(1, substr_count($view, 'id="'.$id.'"'), $id.' présent une seule fois');
        }
        $this->assertStringContainsString('<x-au-select id="matiereSelect"', $view);
        $this->assertStringContainsString('<x-au-select id="periodeFilter"', $view);
        $this->assertStringContainsString('<details class="nm-documents-panel">', $view);
        $this->assertStringContainsString("@canany(['notes.feuille_vierge.export', 'notes.export_excel'])", $view);
        $this->assertStringContainsString("$('#matiereSelect').val('').trigger('change');", $view);
        $this->assertStringNotContainsString('window.location.reload()', $view);
    }

    public function test_dynamic_matter_loading_disables_visible_premium_trigger(): void
    {
        $script = file_get_contents(__DIR__.'/../../resources/views/esbtp/notes/partials/_matieres-classe.blade.php');
        $this->assertStringContainsString("closest('.au-select').find('.au-select-trigger')", $script);
        $this->assertStringContainsString("prop('disabled', true).attr('aria-busy', 'true')", $script);
        $this->assertStringContainsString("prop('disabled', false).removeAttr('aria-busy')", $script);
        $this->assertStringContainsString('if (seq !== nmMatieresSeq) return;', $script);
    }

    public function test_bts_modal_css_keeps_mobile_documents_accessible(): void
    {
        $css = file_get_contents(__DIR__.'/../../public/css/notes-modal-bts-premium.css');
        $this->assertStringContainsString('.nm-documents-popover', $css);
        $this->assertStringContainsString('@media (max-width: 576px)', $css);
        $this->assertStringContainsString('.nm-documents-trigger:focus-visible', $css);
    }
}
