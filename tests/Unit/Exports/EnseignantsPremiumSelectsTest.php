<?php

namespace Tests\Unit\Exports;

use PHPUnit\Framework\TestCase;

final class EnseignantsPremiumSelectsTest extends TestCase
{
    private function liste(): string
    {
        return file_get_contents(dirname(__DIR__, 3).'/resources/views/esbtp/enseignants/index.blade.php');
    }

    private function dialogue(): string
    {
        return file_get_contents(dirname(__DIR__, 3).'/resources/views/components/enseignants-export-dialog.blade.php');
    }

    public function test_les_filtres_de_liste_ne_contiennent_plus_les_options_pdf(): void
    {
        $liste = $this->liste();
        $this->assertSame(2, substr_count($liste, '<x-au-select'));
        $this->assertSame(0, preg_match('/<select\b/i', $liste));
        $this->assertStringContainsString('<x-enseignants-export-dialog', $liste);
        $this->assertStringNotContainsString('window.exportFilters =', $liste);

        foreach (['status', 'specialization'] as $nom) {
            $this->assertMatchesRegularExpression(
                '/<x-au-select\s+name="'.preg_quote($nom, '/').'"/',
                $liste
            );
        }
        foreach (['vue', 'annee_universitaire_id', 'classe_id', 'semestre'] as $nom) {
            $this->assertStringNotContainsString('name="'.$nom.'"', $liste);
        }
    }

    public function test_le_dialogue_contient_tout_et_les_selecteurs_premium(): void
    {
        $vue = $this->dialogue();

        $this->assertSame(5, substr_count($vue, '<x-au-select'));
        $this->assertSame(0, preg_match('/<select\b/i', $vue));
        foreach (['annee_universitaire_id', 'classe_id', 'semestre', 'status', 'specialization'] as $nom) {
            $this->assertMatchesRegularExpression(
                '/<x-au-select\s+name="'.preg_quote($nom, '/').'"/',
                $vue
            );
        }
        $this->assertSame(6, substr_count($vue, 'type="radio" name="vue"'));
        $this->assertStringContainsString('value="matieres"', $vue);
        $this->assertStringContainsString('value="specialisations"', $vue);
        $this->assertStringContainsString('x-teleport="body"', $vue);
        $this->assertStringContainsString('role="dialog"', $vue);
        $this->assertStringContainsString("name=\"search\"", $vue);
    }

    public function test_frontend_bloque_le_detail_pdf_sans_classe_et_signale_les_champs(): void
    {
        $vue = $this->dialogue();

        $this->assertStringContainsString("format !== 'excel' && params.get('vue') === 'affectations'", $vue);
        $this->assertStringContainsString("!params.get('classe_id')", $vue);
        $this->assertStringContainsString("!params.get('annee_universitaire_id')", $vue);
        $this->assertStringContainsString("'te-exp-field--invalid': errors.classe_id", $vue);
        $this->assertStringContainsString("cible?.querySelector('.au-select-trigger')?.focus()", $vue);
        $this->assertStringContainsString('if (Object.keys(errors).length > 0)', $vue);
        $this->assertStringContainsString('new URLSearchParams(new FormData(form))', $vue);
    }

    public function test_les_trois_actions_conservent_les_routes_existantes(): void
    {
        $vue = $this->dialogue();
        foreach (["lancer('preview')", "lancer('pdf')", "lancer('excel')"] as $action) {
            $this->assertStringContainsString($action, $vue);
        }
        $liste = $this->liste();
        $this->assertStringContainsString("route('esbtp.enseignants.preview-pdf')", $liste);
        $this->assertStringContainsString("route('esbtp.enseignants.export-pdf')", $liste);
        $this->assertStringContainsString("route('esbtp.enseignants.export-excel')", $liste);
    }
}
