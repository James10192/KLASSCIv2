<?php

namespace Tests\Unit\Exports;

use PHPUnit\Framework\TestCase;

final class EnseignantsPremiumSelectsTest extends TestCase
{
    private function vue(): string
    {
        return file_get_contents(dirname(__DIR__, 3).'/resources/views/esbtp/enseignants/index.blade.php');
    }

    public function test_les_six_selecteurs_visibles_sont_des_composants_premium(): void
    {
        $vue = $this->vue();

        $this->assertSame(6, substr_count($vue, '<x-au-select'));
        $this->assertSame(0, preg_match('/<select\\b/i', $vue));
        $this->assertStringNotContainsString('te-filter-select', $vue);
        foreach (['status', 'specialization', 'vue', 'annee_universitaire_id', 'classe_id', 'semestre'] as $champ) {
            $this->assertMatchesRegularExpression(
                '/<x-au-select\\s+name="'.preg_quote($champ, '/').'"/',
                $vue
            );
        }
    }

    public function test_les_filtres_sont_initialises_depuis_la_requete_et_transmis_aux_exports(): void
    {
        $vue = $this->vue();

        foreach (['status', 'specialization', 'vue', 'annee_universitaire_id', 'classe_id', 'semestre'] as $champ) {
            $this->assertStringContainsString("request('".$champ."'", $vue);
        }
        $this->assertStringContainsString('new FormData(form).entries()', $vue);
        $this->assertStringContainsString("request('vue', 'synthese')", $vue);
        $this->assertStringContainsString('request(\'annee_universitaire_id\', $anneeExportId)', $vue);
    }

    public function test_les_listes_longues_disposent_de_recherche_et_les_choix_obligatoires_sans_option_vide(): void
    {
        $vue = $this->vue();

        $this->assertSame(3, substr_count($vue, ':searchable='));
        $this->assertSame(2, substr_count($vue, ':placeholder-is-first-option="false"'));
        $this->assertStringContainsString('.te-filter-group .au-select { display:flex; width:100%', $vue);
    }
}
