<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NotesLmdModalAjaxRaceContractTest extends TestCase
{
    private function modal(): string
    {
        return file_get_contents(__DIR__.'/../../resources/views/esbtp/lmd/notes/index.blade.php');
    }

    public function test_classe_data_discard_stale_responses_and_invalidate_on_close_or_year_change(): void
    {
        $view = $this->modal();
        $this->assertStringContainsString('let lmdClasseRequestSeq = 0;', $view);
        $this->assertStringContainsString('const requestSeq = ++lmdClasseRequestSeq;', $view);
        $this->assertStringContainsString('requestSeq !== lmdClasseRequestSeq', $view);
        $this->assertStringContainsString("String(lmdAcademicYearId) !== String(academicYearId)", $view);
        $this->assertStringContainsString("addEventListener('hidden.bs.modal'", $view);
        $this->assertStringContainsString("if (!resp.ok) throw new Error('Chargement des données de classe", $view);
    }

    public function test_ecue_requests_discard_stale_responses_and_reset_selection_on_empty(): void
    {
        $view = $this->modal();
        $this->assertStringContainsString('let lmdEcueRequestSeq = 0;', $view);
        $this->assertStringContainsString('const requestSeq = ++lmdEcueRequestSeq;', $view);
        $this->assertStringContainsString('requestSeq !== lmdEcueRequestSeq', $view);
        $this->assertStringContainsString("document.getElementById('ueSelect').addEventListener('change'", $view);
        $this->assertStringContainsString("document.getElementById('ecueSelect').addEventListener('change'", $view);
        $this->assertStringContainsString('if (!matiereId || !currentClasseId) {', $view);
        $this->assertStringContainsString('Aucune évaluation disponible en consultation', $view);
        $this->assertStringContainsString('lmdMasquerExamensAnonymes();', $view);
        $this->assertStringContainsString('lmdAfficherChargementNotes();', $view);
        $this->assertStringNotContainsString('window.location.reload()', $view);
    }

    public function test_anonymous_exam_panel_has_a_real_hidden_css_rule(): void
    {
        $style = file_get_contents(__DIR__.'/../../public/css/notes-modal-lmd-premium.css');
        $this->assertStringContainsString('#modalNotes #notesAnonymes[hidden] { display: none !important; }', $style);
    }
}
