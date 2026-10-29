<?php

namespace Tests\Feature\LMD;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\LMD\LectureDeMaquetteLmd;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * La lecture doit dire, pour une unite partagee, QUI voit quel element et
 * POURQUOI : c'est tout ce qu'on lui demande quand une ecole signale un element
 * « qui apparait dans l'autre parcours ».
 */
class LectureDeMaquetteLmdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Installation et paywall redirigent avant le controleur : hors sujet ici.
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);

        // Deux parcours d'une meme mention qui importent la MEME unite (meme
        // code) avec chacun leur element : le cas USAT.
        app(LMDImportService::class)->import($this->maquette('LPA', 'Productions Animales', 'ECUE-PA'));
        app(LMDImportService::class)->import($this->maquette('LPV', 'Productions Vegetales', 'ECUE-PV'));
    }

    private function maquette(string $code, string $nom, string $codeEcue): array
    {
        return [
            'domaine' => ['name' => 'Sciences Agronomiques', 'code' => 'SA'],
            'mention' => ['name' => 'Productions', 'code' => 'PVA'],
            'parcours' => ['name' => $nom, 'code' => $code, 'credits_licence' => 180],
            'filiere' => ['name' => $nom, 'code' => 'F'.$code],
            'niveaux' => [['name' => 'Licence 2', 'year' => 2]],
            'ues' => [[
                'code' => 'UE-COMMUNE',
                'name' => 'Unite partagee',
                'credit' => 6,
                'niveau_year' => 2,
                'semestre' => 3,
                'ecues' => [['code' => $codeEcue, 'name' => 'Element '.$codeEcue, 'credit_ecue' => 3]],
            ]],
        ];
    }

    private function vue(array $data, string $parcours): array
    {
        $p = collect($data['parcours'])->firstWhere('code', $parcours);

        return collect($p['unites'][0]['ecues'])->pluck('origine', 'code')->all();
    }

    public function test_chaque_parcours_ne_voit_que_son_element_reserve(): void
    {
        $data = app(LectureDeMaquetteLmd::class)->lire();

        $this->assertArrayNotHasKey('ECUE-PV', $this->vue($data, 'LPA'));
        $this->assertArrayNotHasKey('ECUE-PA', $this->vue($data, 'LPV'));

        $partagee = collect($data['unites_partagees'])->firstWhere('code', 'UE-COMMUNE');
        $this->assertNotNull($partagee, "L'unite importee par deux parcours doit etre signalee comme partagee.");
    }

    public function test_un_element_reserve_qui_porte_aussi_une_ligne_commune_est_signale(): void
    {
        $ue = ESBTPUniteEnseignement::where('code', 'UE-COMMUNE')->firstOrFail();
        $ecue = DB::table('esbtp_matieres')->where('code', 'ECUE-PA')->value('id');

        // Le geste USAT : l'element reserve a LPA est aussi ajoute en
        // composition commune (modal ouvert sans filtre de parcours).
        DB::table('esbtp_ue_matiere')->insert([
            'unite_enseignement_id' => $ue->id,
            'matiere_id' => $ecue,
            'parcours_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = app(LectureDeMaquetteLmd::class)->lire();

        $this->assertSame('commun', $this->vue($data, 'LPV')['ECUE-PA'] ?? null,
            "L'element entre chez l'autre parcours par la ligne commune.");

        $this->assertTrue(collect($data['anomalies'])->contains(fn ($a) => $a['type'] === 'reserve_ailleurs_mais_visible'
            && $a['parcours'] === 'LPV' && str_starts_with($a['ecue'], 'ECUE-PA')));
        $this->assertFalse(collect($data['anomalies'])->contains(fn ($a) => $a['type'] === 'reserve_ailleurs_mais_visible'
            && $a['parcours'] === 'LPA'), 'Chez LPA, l\'element est a sa place.');
    }

    public function test_l_endpoint_exige_cli_read_et_filtre_par_parcours(): void
    {
        $lpa = ESBTPLMDParcours::where('code', 'LPA')->firstOrFail();

        Sanctum::actingAs(User::factory()->create(), ['cli:other']);
        $this->getJson('/api/cli/lmd/maquette')->assertForbidden();

        Sanctum::actingAs(User::factory()->create(), ['cli:read']);
        $this->getJson('/api/cli/lmd/maquette?parcours_id='.$lpa->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.parcours')
            ->assertJsonPath('data.parcours.0.code', 'LPA');
    }
}
