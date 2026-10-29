<?php

namespace Tests\Feature\AcademicPilotage;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Les pages qui portent le bandeau se rendent encore.
 *
 * Un gabarit Blade ne casse pas a la compilation : il casse au rendu, chez
 * l'utilisateur. Poser un `@include` sur des pages de mille a trois mille
 * lignes sans en demander une seule fois le rendu reviendrait a livrer sans
 * avoir regarde. Ces tests demandent l'adresse et verifient que la page sort,
 * et que le bandeau y est.
 */
class PagesAvecBandeauTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    private User $acteur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();

        // Le garde « installed » renvoie vers l'assistant tant qu'aucun
        // superAdmin n'existe : sans lui, tout repondrait 302 et le test ne
        // prouverait rien.
        $superAdmin = Role::findOrCreate('superAdmin', 'web');
        $this->acteur = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->acteur->assignRole($superAdmin);

        Permission::findOrCreate('academic_health.view', 'web');
    }

    public function test_les_resultats_d_une_classe_se_rendent_avec_le_bandeau(): void
    {
        $this->etudiantInscrit();

        $reponse = $this->actingAs($this->acteur)->get(route('esbtp.resultats.classe', [
            'classe' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
        ]));

        $reponse->assertOk();
        $reponse->assertSee('couvertureNotes(', false);
    }

    public function test_la_liste_des_bulletins_se_rend_avec_le_bandeau(): void
    {
        $reponse = $this->actingAs($this->acteur)->get(route('esbtp.bulletins.index', [
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode_id' => 'semestre1',
        ]));

        $reponse->assertOk();
        $reponse->assertSee('couvertureNotes(', false);
    }

    public function test_la_liste_des_evaluations_se_rend_avec_le_bandeau(): void
    {
        $reponse = $this->actingAs($this->acteur)->get(route('esbtp.evaluations.index', [
            'classe_id' => $this->classe->id,
        ]));

        $reponse->assertOk();
        $reponse->assertSee('couvertureNotes(', false);
    }

    public function test_la_selection_de_bulletins_se_rend_avec_le_bandeau(): void
    {
        $reponse = $this->actingAs($this->acteur)->get(route('esbtp.bulletins.select'));

        $reponse->assertOk();
        $reponse->assertSee('couvertureNotes(', false);
        // La carte de generation annonce son contexte a chaque changement.
        $reponse->assertSee('annoncerCouverture', false);
    }

    public function test_la_gestion_des_notes_se_rend_avec_le_bandeau(): void
    {
        $reponse = $this->actingAs($this->acteur)->get(route('esbtp.notes.index'));

        $reponse->assertOk();
        $reponse->assertSee('couvertureNotes(', false);
        $reponse->assertSee('couverture:contexte', false);
    }

    public function test_le_formulaire_d_evaluation_se_rend_avec_le_bandeau(): void
    {
        $reponse = $this->actingAs($this->acteur)->get(route('esbtp.evaluations.create'));

        $reponse->assertOk();
        $reponse->assertSee('couvertureNotes(', false);
    }

    public function test_la_modification_d_une_evaluation_se_rend_avec_le_bandeau(): void
    {
        $evaluation = $this->evaluationDe($this->matiereConfiguree());

        $reponse = $this->actingAs($this->acteur)->get(route('esbtp.evaluations.edit', $evaluation));

        $reponse->assertOk();
        $reponse->assertSee('couvertureNotes(', false);
    }

    public function test_les_resultats_d_un_etudiant_se_rendent_avec_le_bandeau(): void
    {
        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($this->matiereConfiguree()), 12);

        $reponse = $this->actingAs($this->acteur)->get(route('esbtp.resultats.etudiant', [
            'etudiant' => $etudiant->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
        ]));

        $reponse->assertOk();
        $reponse->assertSee('couvertureNotes(', false);
    }
}
