<?php

namespace Tests\Feature\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

/**
 * esbtp-yakro, novembre 2026 : « The filename and the fallback cannot contain
 * the "/" and "\" characters » à l'impression d'un bulletin dont le matricule
 * ou l'année contient une barre oblique.
 */
final class PdfNomDeFichierTest extends TestCase
{
    public function test_un_pdf_dont_le_nom_contient_des_barres_obliques_se_telecharge(): void
    {
        $reponse = Pdf::loadHTML('<p>Bulletin</p>')->download('bulletin_25/ESBTP/001_2025/2026.pdf');

        $this->assertSame(200, $reponse->getStatusCode());
        $entete = (string) $reponse->headers->get('Content-Disposition');
        $this->assertStringContainsString('bulletin_25-ESBTP-001_2025-2026.pdf', $entete);
        $this->assertStringNotContainsString('/', $entete);
    }

    public function test_l_apercu_inline_est_protege_de_la_meme_facon(): void
    {
        $reponse = Pdf::loadHTML('<p>Bulletin</p>')->stream('bulletin_1BTS\\A.pdf');

        $this->assertStringStartsWith('inline', (string) $reponse->headers->get('Content-Disposition'));
        $this->assertStringContainsString('bulletin_1BTS-A.pdf', (string) $reponse->headers->get('Content-Disposition'));
    }
}
