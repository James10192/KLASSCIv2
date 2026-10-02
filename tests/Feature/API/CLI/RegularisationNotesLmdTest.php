<?php

namespace Tests\Feature\API\CLI;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\User;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * La regularisation lit la maquette comme la classe la voit.
 *
 * Sur ESBTP Abidjan, toutes les ECUE sont rattachees a leur UE par la cle
 * etrangere, sans ligne dans esbtp_ue_matiere. L'endpoint ne regardait que ce
 * pivot : il refusait chaque note avec « ne figure pas dans la maquette ».
 */
class RegularisationNotesLmdTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPClasse $classe;

    private ESBTPEtudiant $eleve;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstalled::class,
            EnsureInstalled::class,
            PaywallMiddleware::class,
        ]);

        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        app(LMDImportService::class)->import($this->maquette('TIR', 'ANUM', 2));
        app(LMDImportService::class)->import($this->maquette('BU', 'BUNUM', 2));

        $tir = ESBTPLMDParcours::where('code', 'TIR')->firstOrFail();
        $this->classe = ESBTPClasse::factory()->create([
            // Le systeme se deduit du niveau : celui que l'import a cree.
            'niveau_etude_id' => ESBTPNiveauEtude::where('year', 1)->where('type', 'Licence')->value('id'),
            'parcours_id' => $tir->id,
            'filiere_id' => $tir->filiere_id,
        ]);
        $this->eleve = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->eleve->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        $this->assertSame('LMD', $this->classe->fresh()->systeme_academique);

        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);
    }

    private function maquette(string $code, string $codeEcue, int $semestre): array
    {
        return [
            'domaine' => ['name' => 'Sciences et Technologies', 'code' => 'ST'],
            'mention' => ['name' => 'Genie Civil', 'code' => 'GC'],
            'parcours' => ['name' => 'Parcours '.$code, 'code' => $code, 'credits_licence' => 180],
            'filiere' => ['name' => 'Filiere '.$code, 'code' => 'F'.$code],
            'niveaux' => [['name' => 'Licence 1', 'year' => 1]],
            'ues' => [[
                'code' => 'UE-'.$code,
                'name' => 'Mathematiques '.$code,
                'credit' => 4,
                'niveau_year' => 1,
                'semestre' => $semestre,
                'ecues' => [['code' => $codeEcue, 'name' => 'Analyse numerique '.$code, 'credit_ecue' => 1]],
            ]],
        ];
    }

    private function poster(string $codeEcue, bool $dryRun = true)
    {
        return $this->postJson('/api/cli/lmd/evaluations/regulariser-notes', [
            'etudiant_id' => $this->eleve->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre2',
            'date_regularisation' => '2026-06-30',
            'motif' => 'Releve officiel du semestre 2 transmis par l etablissement',
            'dry_run' => $dryRun,
            'notes' => [['matiere_id' => DB::table('esbtp_matieres')->where('code', $codeEcue)->value('id'), 'note' => 14]],
        ]);
    }

    public function test_une_ecue_rattachee_par_la_seule_cle_etrangere_est_acceptee(): void
    {
        DB::table('esbtp_ue_matiere')->delete();

        $this->poster('ANUM')->assertOk()->assertJsonPath('data.dry_run', true);

        $this->poster('ANUM', false)->assertOk();
        $this->assertSame(14.0, (float) ESBTPNote::where('etudiant_id', $this->eleve->id)->value('note'));
        // Le libellé de l'année est lu par la réinscription : il ne doit pas rester vide.
        $this->assertSame($this->annee->name, ESBTPNote::where('etudiant_id', $this->eleve->id)->value('annee_universitaire'));
    }

    public function test_une_ecue_d_un_autre_parcours_reste_refusee(): void
    {
        DB::table('esbtp_ue_matiere')->delete();

        $this->poster('BUNUM')->assertStatus(422)->assertJsonPath('message', 'La matière Analyse numerique BU ne figure pas dans la maquette S2 de cette classe.');
    }

    public function test_un_autre_semestre_reste_refuse(): void
    {
        $this->postJson('/api/cli/lmd/evaluations/regulariser-notes', [
            'etudiant_id' => $this->eleve->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'date_regularisation' => '2026-06-30',
            'motif' => 'Releve officiel du semestre 2 transmis par l etablissement',
            'notes' => [['matiere_id' => DB::table('esbtp_matieres')->where('code', 'ANUM')->value('id'), 'note' => 14]],
        ])->assertStatus(422)->assertJsonPath('message', 'La matière Analyse numerique TIR ne figure pas dans la maquette S1 de cette classe.');
    }

    public function test_une_ecue_reservee_a_un_autre_parcours_est_refusee(): void
    {
        // L'element de l'unite TIR est reserve, par le pivot, au parcours BU :
        // le bulletin TIR ne le lirait pas, la note ne doit donc pas entrer.
        $bu = ESBTPLMDParcours::where('code', 'BU')->firstOrFail();
        $ecue = DB::table('esbtp_matieres')->where('code', 'ANUM')->value('id');
        DB::table('esbtp_ue_matiere')->where('matiere_id', $ecue)->update(['parcours_id' => $bu->id]);

        $this->poster('ANUM')->assertStatus(422);
    }
}
