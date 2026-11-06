<?php

namespace Tests\Feature\Evaluations;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * « Je n'arrive pas à modifier une évaluation programmée : aucune matière ne
 * s'affiche dans la liste. » Le formulaire proposait le catalogue BTS pour
 * toute classe : une évaluation de classe LMD n'y trouvait pas son ECUE.
 */
class EditionEvaluationMatieresTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_une_evaluation_lmd_retrouve_son_ecue_dans_la_liste(): void
    {
        $licence = ESBTPNiveauEtude::factory()->create(['type' => 'Licence', 'name' => 'Licence 3', 'year' => 3]);
        $classe = ESBTPClasse::factory()->create(['niveau_etude_id' => $licence->id, 'is_active' => true]);
        $this->assertSame('LMD', $classe->fresh()->systeme_academique);

        $ue = ESBTPUniteEnseignement::create(['name' => 'UE Langues', 'code' => 'UE-LANG-'.uniqid()]);
        $ecue = ESBTPMatiere::factory()->create(['name' => 'Anglais technique', 'is_active' => true, 'unite_enseignement_id' => $ue->id]);
        $evaluation = ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id, 'matiere_id' => $ecue->id, 'status' => ESBTPEvaluation::STATUS_SCHEDULED,
        ]);

        $reponse = $this->actingAs($this->superAdministrateur())->get(route('esbtp.evaluations.edit', $evaluation));

        $reponse->assertOk();
        $reponse->assertViewHas('matieres', fn ($liste) => $liste->contains('id', $ecue->id));
        $reponse->assertSee('<option value="'.$ecue->id.'" selected', false);
    }

    public function test_une_evaluation_bts_garde_sa_matiere_et_n_affiche_aucune_ecue(): void
    {
        $classe = ESBTPClasse::factory()->create(['systeme_academique' => 'BTS', 'is_active' => true]);
        $matiere = ESBTPMatiere::factory()->create(['name' => 'Résistance des matériaux', 'is_active' => true]);
        $ue = ESBTPUniteEnseignement::create(['name' => 'UE Langues', 'code' => 'UE-LANG-'.uniqid()]);
        $ecue = ESBTPMatiere::factory()->create(['name' => 'ECUE étrangère', 'is_active' => true, 'unite_enseignement_id' => $ue->id]);
        $evaluation = ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id, 'matiere_id' => $matiere->id, 'status' => ESBTPEvaluation::STATUS_SCHEDULED,
        ]);

        $reponse = $this->actingAs($this->superAdministrateur())->get(route('esbtp.evaluations.edit', $evaluation));

        $reponse->assertOk();
        $reponse->assertViewHas('matieres', fn ($liste) => $liste->contains('id', $matiere->id) && ! $liste->contains('id', $ecue->id));
        $reponse->assertSee('<option value="'.$matiere->id.'" selected', false);
    }

    private function superAdministrateur(): User
    {
        $user = User::withoutEvents(fn () => User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]));
        $user->assignRole(Role::findOrCreate('superAdmin', 'web'));

        return $user;
    }
}
