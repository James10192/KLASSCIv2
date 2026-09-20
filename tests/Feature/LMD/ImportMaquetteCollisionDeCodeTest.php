<?php

namespace Tests\Feature\LMD;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Services\LMD\ConflitDeMaquette;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le code d'une mention ou d'un parcours est unique dans l'ecole. L'import le
 * retrouvait par code puis reecrivait son parent : une mention d'un autre
 * domaine y etait deplacee, avec ses parcours, sans un mot. Le cas est certain
 * quand un meme intitule vit dans deux domaines et que la maquette ne donne pas
 * de code, puisque le code se deduit alors du nom.
 */
class ImportMaquetteCollisionDeCodeTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPLMDDomaine $sciences;

    private ESBTPLMDMention $gestionSciences;

    protected function setUp(): void
    {
        parent::setUp();

        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->sciences = ESBTPLMDDomaine::create(['name' => 'Sciences', 'code' => 'SCI', 'is_active' => true]);
        $this->gestionSciences = ESBTPLMDMention::create([
            'name' => 'Gestion', 'code' => 'gestion', 'domaine_id' => $this->sciences->id, 'is_active' => true,
        ]);
    }

    public function test_une_mention_d_un_autre_domaine_n_est_pas_deplacee(): void
    {
        try {
            app(LMDImportService::class)->import($this->maquette(domaine: 'ECO', mention: ['name' => 'Gestion']));
            $this->fail("L'import aurait dû être refusé.");
        } catch (ConflitDeMaquette $refus) {
            $this->assertSame('MENTION', $refus->conflits()[0]['type']);
        }

        $this->assertSame($this->sciences->id, $this->gestionSciences->fresh()->domaine_id);
    }

    public function test_un_parcours_d_une_autre_mention_n_est_pas_deplace(): void
    {
        $finance = ESBTPLMDParcours::create([
            'name' => 'Finance', 'code' => 'FIN', 'mention_id' => $this->gestionSciences->id, 'is_active' => true,
        ]);

        try {
            app(LMDImportService::class)->import($this->maquette(
                domaine: 'SCI',
                mention: ['name' => 'Comptabilité', 'code' => 'COMPTA'],
                parcours: ['name' => 'Finance', 'code' => 'FIN'],
            ));
            $this->fail("L'import aurait dû être refusé.");
        } catch (ConflitDeMaquette $refus) {
            $this->assertContains('PARCOURS', array_column($refus->conflits(), 'type'));
        }

        $this->assertSame($this->gestionSciences->id, $finance->fresh()->mention_id);
    }

    public function test_reimporter_la_meme_mention_dans_son_domaine_reste_possible(): void
    {
        $resultat = app(LMDImportService::class)->import($this->maquette(domaine: 'SCI', mention: ['name' => 'Gestion']));

        $this->assertSame('gestion', $resultat['mention']['code']);
        $this->assertSame($this->sciences->id, $this->gestionSciences->fresh()->domaine_id);
    }

    private function maquette(string $domaine, array $mention, array $parcours = ['name' => 'Audit', 'code' => 'AUD']): array
    {
        return [
            'domaine' => ['name' => $domaine === 'SCI' ? 'Sciences' : 'Économie', 'code' => $domaine],
            'mention' => $mention,
            'parcours' => $parcours,
            'niveaux' => [],
            'ues' => [],
        ];
    }
}
