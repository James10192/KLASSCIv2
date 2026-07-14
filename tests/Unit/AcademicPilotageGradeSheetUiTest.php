<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AcademicPilotageGradeSheetUiTest extends TestCase
{
    public function test_grade_sheet_cards_use_human_labels_and_keep_the_technical_reference_secondary(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/esbtp/pilotage-academique/index.blade.php');

        $this->assertStringContainsString('sheetTitle(sheet)', $view);
        $this->assertStringContainsString('R&eacute;f&eacute;rence fiche', $view);
        $this->assertStringNotContainsString('<div class="cpa-row-title" x-text="sheet.code"></div>', $view);
        $this->assertStringContainsString('R&eacute;sultats renseign&eacute;s', $view);
        $this->assertStringContainsString('Saisie effectu&eacute;e par', $view);
        $this->assertStringContainsString('R&eacute;ception de la fiche', $view);
        $this->assertStringContainsString('Derni&egrave;re action', $view);
    }

    public function test_grade_sheet_helpers_distinguish_direct_and_paper_workflows(): void
    {
        $script = file_get_contents(__DIR__.'/../../resources/views/esbtp/pilotage-academique/_dashboard-script.blade.php');

        $this->assertStringContainsString("sheet?.entry_mode === 'paper'", $script);
        $this->assertStringContainsString("'Non requise · saisie directe'", $script);
        $this->assertStringContainsString('réception en attente', $script);
        $this->assertStringContainsString('À saisir par', $script);
        $this->assertStringContainsString('statusProgressLabel(sheet)', $script);
        $this->assertStringContainsString('entryProgressPercent(sheet)', $script);
        $this->assertStringContainsString("entries_synced: 'Liste des étudiants synchronisée'", $script);
    }
}
