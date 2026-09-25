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

        $this->assertStringContainsString('margin: 10mm 10mm 20mm 10mm;', $this->rendu());
    }

    public function test_les_marges_de_l_ecole_au_dessus_du_plancher_restent_les_siennes(): void
    {
        $this->marges(25, 12, 24, 30);

        $this->assertStringContainsString('margin: 25mm 12mm 24mm 30mm;', $this->rendu());
    }

    public function test_un_reglage_numerique_vide_prend_son_defaut_et_non_zero(): void
    {
        foreach (['pdf_margin_top', 'pdf_margin_right', 'pdf_margin_bottom', 'pdf_margin_left', 'pdf_logo_size', 'pdf_font_size'] as $cle) {
            DB::table('settings')->updateOrInsert(['key' => $cle], ['value' => '', 'type' => 'integer', 'is_active' => true]);
        }
        Cache::flush();

        $pdf = \App\Helpers\SettingsHelper::getPdfSettings();

        $this->assertSame([20, 15, 20, 15, 60, 12], [$pdf['margin_top'], $pdf['margin_right'], $pdf['margin_bottom'], $pdf['margin_left'], $pdf['logo_size'], $pdf['font_size']]);
        $this->assertStringContainsString('margin: 20mm 15mm 20mm 15mm;', $this->rendu());
    }

    public function test_un_zero_saisi_reste_un_zero_lu(): void
    {
        DB::table('settings')->updateOrInsert(['key' => 'pdf_font_size'], ['value' => '0', 'type' => 'integer', 'is_active' => true]);
        Cache::flush();

        $this->assertSame(0, \App\Helpers\SettingsHelper::entierPose('pdf_font_size', 12));
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
