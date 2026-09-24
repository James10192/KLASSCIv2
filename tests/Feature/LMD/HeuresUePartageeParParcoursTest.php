<?php

namespace Tests\Feature\LMD;

use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La masse horaire d'une UE partagee se saisit maquette par maquette.
 *
 * La fiche d'une UE partagee ne porte que la filiere du premier parcours
 * importe. Le serveur l'imposait a toute saisie : les heures tapees sur la
 * maquette LPA partaient dans la planification de LPV (USAT).
 */
class HeuresUePartageeParParcoursTest extends TestCase
{
    use RefreshDatabase;

    private User $acteur;

    private ESBTPMatiere $ecue;

    private ESBTPLMDParcours $lpv;

    private ESBTPLMDParcours $lpa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            PaywallMiddleware::class,
        ]);

        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);

        // LPV importe l'UE en premier : sa fiche porte la filiere de LPV.
        app(LMDImportService::class)->import($this->maquette('LPV', 'AGR21031'));
        app(LMDImportService::class)->import($this->maquette('LPA', 'AGR21033'));

        $this->ecue = ESBTPMatiere::where('code', 'AGR21031')->firstOrFail();
        $this->lpv = ESBTPLMDParcours::where('code', 'LPV')->firstOrFail();
        $this->lpa = ESBTPLMDParcours::where('code', 'LPA')->firstOrFail();

        // L'import reserve AGR21031 a LPV. On le rend commun : les deux
        // maquettes le voient, chacune doit garder ses heures.
        $ue = ESBTPUniteEnseignement::where('code', 'AGR2103')->firstOrFail();
        app(\App\Services\LMD\CompositionUe::class)->poser($ue, (int) $this->ecue->id, ['credit_ecue' => 2]);
        DB::table('esbtp_ue_matiere')->where('unite_enseignement_id', $ue->id)
            ->where('matiere_id', $this->ecue->id)->where('parcours_id', $this->lpv->id)->delete();

        foreach (['admin.access', 'module.lmd.access', 'lmd.planning.view', 'lmd.planning.edit'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])
            ->assignRole(Role::findOrCreate('superAdmin', 'web'));
        $this->acteur = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $this->acteur->givePermissionTo(['admin.access', 'module.lmd.access', 'lmd.planning.view', 'lmd.planning.edit']);
    }

    private function maquette(string $code, string $codeEcue): array
    {
        return [
            'domaine' => ['name' => 'Sciences Agronomiques', 'code' => 'SA'],
            'mention' => ['name' => 'Productions', 'code' => 'PVA'],
            'parcours' => ['name' => 'Parcours '.$code, 'code' => $code, 'credits_licence' => 180],
            'filiere' => ['name' => 'Filiere '.$code, 'code' => 'F'.$code],
            'niveaux' => [['name' => 'Licence 2', 'year' => 2]],
            'ues' => [[
                'code' => 'AGR2103', 'name' => 'Amelioration genetique', 'credit' => 4,
                'niveau_year' => 2, 'semestre' => 3,
                'ecues' => [['code' => $codeEcue, 'name' => 'Element '.$codeEcue, 'credit_ecue' => 2]],
            ]],
        ];
    }

    private function saisir(?int $filiereId, int $cm, ?ESBTPMatiere $ecue = null)
    {
        $niveau = ESBTPUniteEnseignement::where('code', 'AGR2103')->value('niveau_id');

        return $this->actingAs($this->acteur)
            ->patchJson(route('esbtp.lmd.planifications.update', ($ecue ?? $this->ecue)->id), array_filter([
                'filiere_id' => $filiereId,
                'niveau_id' => $niveau,
                'semestre' => 3,
                'volume_horaire_cm' => $cm,
            ], fn ($v) => $v !== null));
    }

    public function test_les_heures_saisies_sur_lpa_vont_dans_la_planification_de_lpa(): void
    {
        $this->saisir((int) $this->lpv->filiere_id, 20)->assertOk();
        $this->saisir((int) $this->lpa->filiere_id, 30)->assertOk();

        $heures = fn (ESBTPLMDParcours $p) => ESBTPPlanificationAcademique::where('matiere_id', $this->ecue->id)
            ->where('filiere_id', $p->filiere_id)->where('semestre', 3)->value('volume_horaire_cm');

        $this->assertSame(20, (int) $heures($this->lpv), 'Les heures de LPV ne doivent pas etre ecrasees par la saisie de LPA.');
        $this->assertSame(30, (int) $heures($this->lpa), 'Les heures saisies sur la maquette LPA doivent y rester.');
    }

    public function test_une_filiere_etrangere_a_l_ecue_est_refusee_sans_rien_ecrire(): void
    {
        $etrangere = ESBTPFiliere::create(['name' => 'Droit', 'code' => 'DRT', 'is_active' => true]);

        $this->saisir((int) $etrangere->id, 12)->assertStatus(422);

        $this->assertFalse(
            ESBTPPlanificationAcademique::where('matiere_id', $this->ecue->id)->where('volume_horaire_cm', 12)->exists(),
            'Aucune maquette ne doit recevoir ces heures, surtout pas celle de la fiche.'
        );
    }

    public function test_un_ecue_reserve_a_lpv_est_refuse_sur_la_maquette_lpa(): void
    {
        $reserveLpa = ESBTPMatiere::where('code', 'AGR21033')->firstOrFail();

        $this->saisir((int) $this->lpv->filiere_id, 15, $reserveLpa)->assertStatus(422);
        $this->saisir((int) $this->lpa->filiere_id, 15, $reserveLpa)->assertOk();
    }

    public function test_sans_filiere_une_ue_partagee_est_refusee(): void
    {
        $this->saisir(null, 9)->assertStatus(422);
        $this->assertFalse(ESBTPPlanificationAcademique::where('volume_horaire_cm', 9)->exists());
    }

    public function test_la_saisie_en_masse_range_aussi_par_parcours(): void
    {
        $niveau = ESBTPUniteEnseignement::where('code', 'AGR2103')->value('niveau_id');

        $this->actingAs($this->acteur)
            ->postJson(route('esbtp.lmd.planifications.bulk-update'), [
                'ecue_ids' => [$this->ecue->id],
                'fields' => ['volume_horaire_td' => 14],
                'filiere_id' => $this->lpa->filiere_id,
                'niveau_id' => $niveau,
                'semestre' => 3,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(14, (int) ESBTPPlanificationAcademique::where('matiere_id', $this->ecue->id)
            ->where('filiere_id', $this->lpa->filiere_id)->value('volume_horaire_td'));
        $this->assertFalse(ESBTPPlanificationAcademique::where('filiere_id', $this->lpv->filiere_id)
            ->where('volume_horaire_td', 14)->exists());
    }
}
