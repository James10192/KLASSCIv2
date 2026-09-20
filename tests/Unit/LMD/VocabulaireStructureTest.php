<?php

namespace Tests\Unit\LMD;

use App\Enums\NatureComposante;
use App\Models\ESBTPLMDDomaine;
use App\Services\LMD\VocabulaireStructure;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Trois rangs, toujours ; le mot change d'une universite a l'autre, et le
 * premier rang peut dire ce qu'il est (UFR, ecole...). Reglages poses dans le
 * cache (`setting_<cle>`), sans base de donnees.
 */
class VocabulaireStructureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        Cache::flush();
    }

    public function test_par_defaut_le_vocabulaire_du_referentiel_lmd(): void
    {
        $this->regler('Domaine', 'Mention', 'Parcours');

        $this->assertSame(
            ['domaine' => 'Domaine', 'mention' => 'Mention', 'parcours' => 'Parcours'],
            (new VocabulaireStructure())->tous()
        );
    }

    public function test_une_universite_nomme_ses_rangs(): void
    {
        $this->regler('Composante', 'Département', 'Spécialité');
        $vocabulaire = new VocabulaireStructure();

        $this->assertSame('Département', $vocabulaire->mention());
        $this->assertSame('Spécialité', $vocabulaire->parcours());
    }

    public function test_un_libelle_vide_retombe_sur_le_referentiel(): void
    {
        $this->regler('   ', 'Mention', 'Parcours');

        $this->assertSame('Domaine', (new VocabulaireStructure())->domaine());
    }

    public function test_la_nature_prime_sur_le_nom_du_rang(): void
    {
        $this->regler('Composante', 'Département', 'Spécialité');
        $vocabulaire = new VocabulaireStructure();

        $ecole = new ESBTPLMDDomaine(['nature' => NatureComposante::ECOLE]);
        $sansNature = new ESBTPLMDDomaine();

        $this->assertSame('École', $vocabulaire->natureDe($ecole));
        $this->assertSame('Composante', $vocabulaire->natureDe($sansNature));
    }

    public function test_pluriel_des_intitules_de_rang(): void
    {
        $vocabulaire = new VocabulaireStructure();

        $this->assertSame('Domaines', $vocabulaire->pluriel('Domaine'));
        $this->assertSame('Parcours', $vocabulaire->pluriel('Parcours'));
        $this->assertSame('Spécialités', $vocabulaire->pluriel('Spécialité'));
    }

    public function test_les_natures_se_lisent_dans_l_enumeration(): void
    {
        $this->assertSame(['ufr', 'faculte', 'ecole', 'institut'], NatureComposante::values());
        $this->assertSame('Unité de Formation et de Recherche', NatureComposante::UFR->intitule());
    }

    private function regler(string $domaine, string $mention, string $parcours): void
    {
        Cache::put('setting_'.VocabulaireStructure::CLE_DOMAINE, $domaine, 60);
        Cache::put('setting_'.VocabulaireStructure::CLE_MENTION, $mention, 60);
        Cache::put('setting_'.VocabulaireStructure::CLE_PARCOURS, $parcours, 60);
    }
}
