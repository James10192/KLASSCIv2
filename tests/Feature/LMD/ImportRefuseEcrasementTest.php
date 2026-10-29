<?php

namespace Tests\Feature\LMD;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\ConflitDeMaquette;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Importer la maquette d'un parcours ne detruit pas celle d'un autre.
 *
 * Le code d'une UE est unique dans toute la base. L'import la retrouvait donc par
 * son code, puis reecrivait `parcours_id`, `semestre`, `credit` et `niveau_id`.
 * Importer le second parcours REECRIVAIT le premier, en silence.
 *
 * Depuis que les pivots portent le partage, l'import PARTAGE l'unite : la fiche
 * reste au premier parcours, le second y est rattache avec son semestre et son
 * credit propres, et ses elements lui sont reserves. Le refus ne vaut plus que
 * pour ce que les pivots ne savent pas dire — un ECUE deja rattache a une AUTRE
 * unite, que l'import reparenterait — et il refuse SANS RIEN ECRIRE.
 */
class ImportRefuseEcrasementTest extends TestCase
{
    use RefreshDatabase;

    private LMDImportService $import;

    protected function setUp(): void
    {
        parent::setUp();

        // L'import exige une annee courante (LMDImportService:49). La fabrique la
        // cree a is_current = false : sans ce reglage, les trois cas echouent avant
        // d'atteindre ce qu'ils verifient.
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);

        $this->import = app(LMDImportService::class);
    }

    public function test_un_second_parcours_partage_l_unite_sans_reecrire_la_maquette_du_premier(): void
    {
        $this->import->import($this->maquette('BU', 'Batiment', 1, 6));

        $avant = ESBTPUniteEnseignement::where('code', 'UE-PARTAGEE')->firstOrFail();

        $resultat = $this->import->import($this->maquette('TP', 'Travaux Publics', 3, 9));
        $this->assertIsArray($resultat);

        // La fiche de l'unite reste celle du premier parcours.
        $apres = ESBTPUniteEnseignement::where('code', 'UE-PARTAGEE')->firstOrFail();
        $this->assertSame(1, (int) $apres->semestre, 'Le semestre du premier parcours a ete ecrase.');
        $this->assertSame(6, (int) $apres->credit, 'Le credit du premier parcours a ete ecrase.');
        $this->assertSame((int) $avant->parcours_id, (int) $apres->parcours_id);

        // Le second parcours tient l'unite par le lien, avec ce qui lui est propre.
        $batiment = ESBTPLMDParcours::where('code', 'BU')->firstOrFail();
        $travauxPublics = ESBTPLMDParcours::where('code', 'TP')->firstOrFail();

        $lienTp = DB::table('esbtp_lmd_parcours_ue')
            ->where('parcours_id', $travauxPublics->id)
            ->where('unite_enseignement_id', $apres->id)
            ->first();
        $this->assertNotNull($lienTp, "Travaux Publics n'est pas rattache a l'unite partagee.");
        $this->assertSame(3, (int) $lienTp->semestre);
        $this->assertSame(9, (int) $lienTp->credit, 'Le credit propre a Travaux Publics doit etre sur son lien.');

        $lienBu = DB::table('esbtp_lmd_parcours_ue')
            ->where('parcours_id', $batiment->id)
            ->where('unite_enseignement_id', $apres->id)
            ->first();
        $this->assertNotNull($lienBu);
        $this->assertNull($lienBu->credit, 'Batiment prend le credit de la fiche : pas de credit propre.');

        // Chaque maquette ne voit que ses elements.
        $this->assertSame(['ECUE-BU'], $apres->fresh()->getEcuesEffectifs($batiment->id)->pluck('code')->all());
        $this->assertSame(['ECUE-TP'], $apres->fresh()->getEcuesEffectifs($travauxPublics->id)->pluck('code')->all());
    }

    public function test_rien_n_est_ecrit_quand_l_import_refuse(): void
    {
        $this->import->import($this->maquette('BU', 'Batiment', 1, 6));

        $uesAvant = ESBTPUniteEnseignement::count();
        $ecuesAvant = ESBTPMatiere::count();

        // Une unite nouvelle qui reclame l'ECUE d'une autre : l'import le
        // reparenterait et le retirerait de la premiere. C'est le seul cas qui
        // reste refuse.
        try {
            $this->import->import($this->maquette('TP', 'Travaux Publics', 3, 9, codeUe: 'UE-TP-SEULE', codeEcue: 'ECUE-BU'));
            $this->fail("L'import aurait du refuser de reparenter l'ECUE d'une autre unite.");
        } catch (ConflitDeMaquette $e) {
            $this->assertNotEmpty($e->conflits());
            $this->assertSame('ECUE', $e->conflits()[0]['type']);
            $this->assertSame('ECUE-BU', $e->conflits()[0]['code']);
        }

        // L'import tourne dans une transaction : le refus annule TOUT, y compris le
        // domaine, la mention et le parcours que la seconde maquette aurait crees.
        $this->assertSame($uesAvant, ESBTPUniteEnseignement::count(), 'Des UE ont ete creees malgre le refus.');
        $this->assertSame($ecuesAvant, ESBTPMatiere::count(), 'Des ECUE ont ete crees malgre le refus.');
        $this->assertSame(1, (int) ESBTPUniteEnseignement::where('code', 'UE-PARTAGEE')->firstOrFail()->semestre);
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
    private function maquette(string $codeParcours, string $nomParcours, int $semestre, int $credit, string $codeUe = 'UE-PARTAGEE', ?string $codeEcue = null): array
    {
        return [
            'domaine' => ['name' => 'Sciences et Technologies', 'code' => 'ST'],
            'mention' => ['name' => 'Genie Civil', 'code' => 'GC'],
            'parcours' => ['name' => $nomParcours, 'code' => $codeParcours, 'credits_licence' => 180],
            'filiere' => ['name' => $nomParcours, 'code' => 'F'.$codeParcours],
            'niveaux' => [['name' => 'Licence 1', 'year' => 1]],
            'ues' => [[
                'code' => $codeUe,
                'name' => 'Unite '.$codeUe,
                'credit' => $credit,
                'niveau_year' => 1,
                'semestre' => $semestre,
                'ecues' => [[
                    'code' => $codeEcue ?? 'ECUE-'.$codeParcours,
                    'name' => 'Matiere '.($codeEcue ?? $codeParcours),
                    'credit_ecue' => 3,
                ]],
            ]],
        ];
    }
}
