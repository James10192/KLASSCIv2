<?php

namespace Tests\Feature\LMD;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\ConflitDeMaquette;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Importer la maquette d'un parcours ne detruit pas celle d'un autre.
 *
 * Le code d'une UE est unique dans toute la base. L'import la retrouvait donc par
 * son code, puis reecrivait `parcours_id`, `semestre`, `credit` et `niveau_id`.
 * Importer le second parcours REECRIVAIT le premier, en silence : il heritait du
 * semestre et du credit de l'autre. Meme chose pour un ECUE, reparente vers une
 * autre UE, donc disparu de la premiere.
 *
 * Sur esbtp-abidjan, cela a oblige a renommer cinq ECUE a la main lors de l'import
 * du Genie Civil, et sept UE y sont rattachees a deux semestres.
 *
 * Le partage reel demande des colonnes qui n'existent pas encore (issue #942). En
 * attendant, on refuse — et on refuse SANS RIEN ECRIRE.
 */
class ImportRefuseEcrasementTest extends TestCase
{
    use RefreshDatabase;

    private LMDImportService $import;

    protected function setUp(): void
    {
        parent::setUp();
        $this->import = app(LMDImportService::class);
    }

    public function test_un_second_parcours_ne_reecrit_pas_la_maquette_du_premier(): void
    {
        $this->import->import($this->maquette('BU', 'Batiment', 1, 6));

        $avant = ESBTPUniteEnseignement::where('code', 'UE-PARTAGEE')->firstOrFail();
        $this->assertSame(1, (int) $avant->semestre);
        $this->assertSame(6, (int) $avant->credit);

        try {
            $this->import->import($this->maquette('TP', 'Travaux Publics', 3, 9));
            $this->fail("L'import aurait du refuser d'ecraser la maquette du premier parcours.");
        } catch (ConflitDeMaquette $e) {
            $this->assertNotEmpty($e->conflits());
            $this->assertSame('UE', $e->conflits()[0]['type']);
            $this->assertSame('UE-PARTAGEE', $e->conflits()[0]['code']);
        }

        // Le point qui compte : rien n'a bouge.
        $apres = ESBTPUniteEnseignement::where('code', 'UE-PARTAGEE')->firstOrFail();
        $this->assertSame(1, (int) $apres->semestre, 'Le semestre du premier parcours a ete ecrase.');
        $this->assertSame(6, (int) $apres->credit, 'Le credit du premier parcours a ete ecrase.');
        $this->assertSame((int) $avant->parcours_id, (int) $apres->parcours_id);
    }

    public function test_rien_n_est_ecrit_quand_l_import_refuse(): void
    {
        $this->import->import($this->maquette('BU', 'Batiment', 1, 6));

        $uesAvant = ESBTPUniteEnseignement::count();
        $ecuesAvant = ESBTPMatiere::count();

        try {
            $this->import->import($this->maquette('TP', 'Travaux Publics', 3, 9));
        } catch (ConflitDeMaquette $e) {
            // attendu
        }

        // L'import tourne dans une transaction : le refus annule TOUT, y compris le
        // domaine, la mention et le parcours que la seconde maquette aurait crees.
        $this->assertSame($uesAvant, ESBTPUniteEnseignement::count(), 'Des UE ont ete creees malgre le refus.');
        $this->assertSame($ecuesAvant, ESBTPMatiere::count(), 'Des ECUE ont ete crees malgre le refus.');
    }

    public function test_reimporter_le_meme_parcours_reste_possible(): void
    {
        $this->import->import($this->maquette('BU', 'Batiment', 1, 6));

        // Idempotence : corriger et rejouer sa propre maquette ne doit jamais etre
        // refuse, sinon on rend l'outil inutilisable.
        $resultat = $this->import->import($this->maquette('BU', 'Batiment', 1, 8));

        $this->assertIsArray($resultat);
        $this->assertSame(8, (int) ESBTPUniteEnseignement::where('code', 'UE-PARTAGEE')->firstOrFail()->credit);
    }

    /**
     * Une maquette minimale : un domaine, une mention, un parcours, une UE et un ECUE.
     */
    private function maquette(string $codeParcours, string $nomParcours, int $semestre, int $credit): array
    {
        return [
            'domaine' => ['name' => 'Sciences et Technologies', 'code' => 'ST'],
            'mention' => ['name' => 'Genie Civil', 'code' => 'GC'],
            'parcours' => ['name' => $nomParcours, 'code' => $codeParcours, 'credits_licence' => 180],
            'filiere' => ['name' => $nomParcours, 'code' => 'F'.$codeParcours],
            'niveaux' => [['name' => 'Licence 1', 'year' => 1]],
            'ues' => [[
                'code' => 'UE-PARTAGEE',
                'name' => 'Unite partagee',
                'credit' => $credit,
                'niveau_year' => 1,
                'semestre' => $semestre,
                'ecues' => [[
                    'code' => 'ECUE-'.$codeParcours,
                    'name' => 'Matiere '.$codeParcours,
                    'credit_ecue' => 3,
                ]],
            ]],
        ];
    }
}
