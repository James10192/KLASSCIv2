<?php

namespace Tests\Unit\Services;

use App\Services\ESBTP\BtsProvisionalTranscript;
use PHPUnit\Framework\TestCase;

/** Garde-fous sans base : aucun PDF vide ni fausse moyenne officielle. */
class BtsProvisionalTranscriptTest extends TestCase
{
    public function test_aucune_note_ne_cree_un_releve_provisoire(): void
    {
        $this->assertFalse(BtsProvisionalTranscript::hasResults([]));
        $this->assertFalse(BtsProvisionalTranscript::hasResults([
            ['subjects' => [], 'notes_count' => 0, 'manual_resultats_count' => 0],
            ['subjects' => [], 'notes_count' => 0, 'manual_resultats_count' => 0],
        ]));
    }

    public function test_les_notes_et_les_resultats_manuels_peuvent_alimenter_le_provisoire(): void
    {
        $subjects = [['matiere' => 'Mathématiques', 'moyenne' => 0.0]];
        $this->assertTrue(BtsProvisionalTranscript::hasResults([
            ['subjects' => $subjects, 'notes_count' => 1, 'manual_resultats_count' => 0],
        ]));
        $this->assertTrue(BtsProvisionalTranscript::hasResults([
            ['subjects' => $subjects, 'notes_count' => 0, 'manual_resultats_count' => 1],
        ]));
        $this->assertFalse(BtsProvisionalTranscript::hasResults([
            ['subjects' => $subjects, 'notes_count' => 0, 'manual_resultats_count' => 0],
        ]));
    }

    public function test_le_pdf_provisoire_est_distinct_du_document_publie(): void
    {
        $base = dirname(__DIR__, 3);
        $provisoire = file_get_contents($base.'/resources/views/pdf/bts-releve-provisoire.blade.php');
        $publie = file_get_contents($base.'/resources/views/pdf/bts-releve-interne.blade.php');
        $service = file_get_contents($base.'/app/Services/ESBTP/BtsProvisionalTranscript.php');

        $this->assertStringContainsString('DOCUMENT CONSULTATIF — NON OFFICIEL — NON PUBLIÉ', $provisoire);
        $this->assertStringContainsString('<x-pdf-document', $provisoire);
        $this->assertStringContainsString('NE PAS UTILISER COMME DOCUMENT OFFICIEL', $provisoire);
        $this->assertStringContainsString('résultats publiés', $publie);
        $this->assertStringContainsString('getSemesterSnapshot', $service);
        $this->assertStringContainsString('$records->classMap($inscription)', $service);
        $this->assertStringContainsString("'semestre1', 'semestre2'", $service);
    }
}
