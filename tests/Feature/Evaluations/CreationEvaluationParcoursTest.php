<?php

namespace Tests\Feature\Evaluations;

use App\Models\ESBTPClasse;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreationEvaluationParcoursTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Sans instance installee, CheckInstalled redirige avant le controleur.
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (['identity.teach', 'identity.coordinate', 'evaluations.create', 'admin.access', 'module.notes_evaluations.access'] as $nom) {
            Permission::findOrCreate($nom, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_un_super_administrateur_peut_assigner_un_enseignant(): void
    {
        $enseignant = $this->utilisateur();
        $enseignant->givePermissionTo('identity.teach');

        $reponse = $this->actingAs($this->superAdministrateur())->get(route('esbtp.evaluations.create'));

        $reponse->assertOk();
        $reponse->assertViewHas('assigneUnEnseignant', true);
        $reponse->assertViewHas('enseignants', fn ($liste) => $liste->contains('id', $enseignant->id));
    }

    public function test_un_enseignant_ne_choisit_pas_l_enseignant(): void
    {
        $enseignant = $this->utilisateur();
        $enseignant->givePermissionTo(['identity.teach', 'evaluations.create', 'admin.access', 'module.notes_evaluations.access']);

        $reponse = $this->actingAs($enseignant)->get(route('esbtp.evaluations.create'));

        $reponse->assertOk();
        $reponse->assertViewHas('assigneUnEnseignant', false);
    }

    public function test_les_classes_lmd_ne_sont_pas_proposees_dans_le_formulaire_bts(): void
    {
        $bts = ESBTPClasse::factory()->create(['systeme_academique' => 'BTS', 'is_active' => true]);
        $lmd = $this->classeLmd();

        $reponse = $this->actingAs($this->superAdministrateur())->get(route('esbtp.evaluations.create'));

        $reponse->assertOk();
        $reponse->assertViewHas('classes', fn ($classes) => $classes->contains('id', $bts->id) && ! $classes->contains('id', $lmd->id));
        $reponse->assertViewHas('classesLmdEcartees', fn ($n) => $n >= 1);
        $reponse->assertSee('les notes LMD', false);
    }

    public function test_une_classe_lmd_choisie_reste_proposee(): void
    {
        $lmd = $this->classeLmd();

        $reponse = $this->actingAs($this->superAdministrateur())->get(route('esbtp.evaluations.create', ['classe_id' => $lmd->id]));

        $reponse->assertOk();
        $reponse->assertViewHas('classes', fn ($classes) => $classes->contains('id', $lmd->id));
    }

    /** Le modele deduit le systeme du niveau a l'enregistrement : il faut un niveau Licence. */
    private function classeLmd(): ESBTPClasse
    {
        $licence = ESBTPNiveauEtude::factory()->create(['type' => 'Licence', 'name' => 'Licence 1', 'year' => 1]);
        $classe = ESBTPClasse::factory()->create(['niveau_etude_id' => $licence->id, 'is_active' => true]);
        $this->assertSame('LMD', $classe->fresh()->systeme_academique);

        return $classe;
    }

    private function superAdministrateur(): User
    {
        $user = $this->utilisateur();
        $user->assignRole(Role::findOrCreate('superAdmin', 'web'));

        return $user;
    }

    private function utilisateur(): User
    {
        return User::withoutEvents(fn () => User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]));
    }
}
