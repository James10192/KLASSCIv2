<?php

namespace Tests\Feature\Exports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Les marges d'un document <x-pdf-document> : celles de l'ecole, jamais
 * sous le plancher d'impression.
 */
class PdfDocumentMargesTest extends TestCase
{
    use RefreshDatabase;

    public function test_une_marge_a_zero_garde_le_plancher_d_impression(): void
    {
        $this->marges(0, 0, 0, 0);

        $this->assertStringContainsString('margin: 10mm 10mm 10mm 10mm;', $this->rendu());
    }

    public function test_les_marges_de_l_ecole_au_dessus_du_plancher_restent_les_siennes(): void
    {
        $this->marges(25, 12, 18, 30);

        $this->assertStringContainsString('margin: 25mm 12mm 18mm 30mm;', $this->rendu());
    }

    private function marges(int $haut, int $droite, int $bas, int $gauche): void
    {
        foreach (['top' => $haut, 'right' => $droite, 'bottom' => $bas, 'left' => $gauche] as $cote => $valeur) {
            DB::table('settings')->updateOrInsert(['key' => 'pdf_margin_'.$cote], ['value' => (string) $valeur]);
        }
        Cache::flush();
    }

    private function rendu(): string
    {
        return Blade::render('<x-pdf-document title="Essai">corps</x-pdf-document>');
    }
}
