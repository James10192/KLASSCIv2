<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NotesLmdGridFirstLayoutTest extends TestCase
{
    public function test_follow_up_partials_are_inside_single_collapsible_panel(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/esbtp/lmd/notes/index.blade.php');
        $panel = strpos($view, '<details class="ln-secondary-panel"');
        $requalification = strpos($view, "@include('esbtp.lmd.notes.partials._requalification')");
        $suivi = strpos($view, "@include('esbtp.partials._couverture-notes'");
        $grid = strpos($view, 'class="ln-grid-wrap" id="notesGridWrap"');
        $this->assertNotFalse($panel);
        $this->assertTrue($panel < $requalification && $requalification < $suivi && $suivi < $grid);
        $this->assertSame(1, substr_count($view, "@include('esbtp.partials._couverture-notes'"));
        $this->assertSame(1, substr_count($view, "@include('esbtp.lmd.notes.partials._requalification'"));
        $this->assertStringContainsString('id="ln-requal-count"', $view);
    }

    public function test_lmd_grade_entry_ajax_contracts_are_preserved(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/esbtp/lmd/notes/index.blade.php');
        foreach (['ueSelect', 'ecueSelect', 'periodeFilter', 'notesGrid',
            'studentsRows', 'notesGridWrap', 'notesLoading', 'notesEmpty',
            'createEvalBtn', 'saveAllNotesBtn', 'validateNotesBtn', 'autosaveStatusText'] as $id) {
            $this->assertSame(1, substr_count($view, 'id="'.$id.'"'), $id);
        }
        $this->assertStringContainsString('<x-au-select id="ueSelect"', $view);
        $this->assertStringContainsString('<x-au-select id="ecueSelect"', $view);
        $this->assertStringContainsString('<x-au-select id="periodeFilter"', $view);
        $this->assertStringNotContainsString('window.location.reload()', $view);
    }

    public function test_requalification_counter_follows_real_backend_data(): void
    {
        $partial = file_get_contents(__DIR__.'/../../resources/views/esbtp/lmd/notes/partials/_requalification.blade.php');
        $this->assertStringContainsString("getElementById('ln-requal-count')", $partial);
        $this->assertStringContainsString('requalLignes.length === 0', $partial);
        $this->assertStringContainsString('badge.hidden = false', $partial);
        $this->assertStringContainsString('badge.hidden = requalLignes.length === 0', $partial);
    }

    public function test_grid_uses_own_scroll_at_multiple_viewports(): void
    {
        $css = file_get_contents(__DIR__.'/../../public/css/notes-modal-lmd-premium.css');
        $this->assertStringContainsString('.ln-secondary-popover', $css);
        $this->assertStringContainsString('overflow: hidden !important', $css);
        $this->assertStringContainsString('overflow: auto !important', $css);
        $this->assertStringContainsString('@media (max-width: 700px)', $css);
    }
}
