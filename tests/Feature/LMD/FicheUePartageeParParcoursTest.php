<?php

namespace Tests\Feature\LMD;

use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La fiche d'une UE partagee montre chaque maquette a part.
 *
 * Elle melangeait les elements de tous les parcours et lisait les heures dans
 * la filiere du premier parcours importe (USAT).
 */
class FicheUePartageeParParcoursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            PaywallMiddleware::class,
        ]);

        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        app(LMDImportService::class)->import($this->maquette('LPV', 'AGR21031', 'Genetique vegetale'));
        app(LMDImportService::class)->import($this->maquette('LPA', 'AGR21033', 'Genetique animale'));
    }

    private function maquette(string $code, string $codeEcue, string $nom): array
    {
        return [
            'domaine' => ['name' => 'Sciences Agronomiques', 'code' => 'SA'],
            'mention' => ['name' => 'Productions', 'code' => 'PVA'],
            'parcours' => ['name' => 'Parcours '.$code, 'code' => $code, 'credits_licence' => 180],
            'filiere' => ['name' => 'Filiere '.$code, 'code' => 'F'.$code],
            'niveaux' => [['name' => 'Licence 2', 'year' => 2]],
            'ues' => [[
                'code' => 'AGR2103', 'name' => 'Amelioration genetique', 'credit' => 2,
                'niveau_year' => 2, 'semestre' => 3,
                'ecues' => [['code' => $codeEcue, 'name' => $nom, 'credit_ecue' => 2]],
            ]],
        ];
    }

    public function test_chaque_parcours_a_ses_elements_et_ses_heures(): void
    {
        $ue = ESBTPUniteEnseignement::where('code', 'AGR2103')->firstOrFail();
        $lpa = ESBTPLMDParcours::where('code', 'LPA')->firstOrFail();
        $animale = ESBTPMatiere::where('code', 'AGR21033')->firstOrFail();

        ESBTPPlanificationAcademique::updateOrCreate(
            ['matiere_id' => $animale->id, 'filiere_id' => $lpa->filiere_id, 'niveau_etude_id' => $ue->niveau_id,
             'semestre' => 3, 'annee_universitaire_id' => ESBTPAnneeUniversitaire::where('is_current', true)->value('id')],
            ['volume_horaire_cm' => 37, 'volume_horaire_total' => 37, 'credits_ects' => 2]
        );

        $acteur = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $acteur->assignRole(Role::findOrCreate('superAdmin', 'web'));

        $maquettes = $this->actingAs($acteur)->get(route('esbtp.lmd.ue.show', $ue))
            ->assertOk()
            ->viewData('maquettes');

        $parCode = collect($maquettes)->keyBy(fn ($m) => $m['parcours']->code);

        $this->assertSame(['AGR21033'], $parCode['LPA']['ecues']->pluck('code')->all(), 'LPA ne voit que sa maquette.');
        $this->assertSame(['AGR21031'], $parCode['LPV']['ecues']->pluck('code')->all(), 'LPV ne voit que la sienne.');
        $this->assertSame(37, $parCode['LPA']['heures'], 'Les heures de LPA viennent de la filiere de LPA.');
        $this->assertSame(0, $parCode['LPV']['heures'], 'LPV n\'a rien saisi.');
    }
}
