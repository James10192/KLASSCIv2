<?php

namespace Tests\Feature\Notes;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PreviewImpactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('notes.view', 'web');
        Permission::findOrCreate('notes.create', 'web');
        Permission::findOrCreate('notes.edit', 'web');
        Permission::findOrCreate('module.notes_evaluations.access', 'web');
        Permission::findOrCreate('admin.access', 'web');
    }

    private function authUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('notes.view');
        $user->givePermissionTo('notes.create');
        $user->givePermissionTo('notes.edit');
        $user->givePermissionTo('module.notes_evaluations.access');
        $user->givePermissionTo('admin.access');
        $this->actingAs($user);

        return $user;
    }

    private function makeContext(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create(['coefficient' => 2]);

        $eval1 = ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $annee->id,
            'is_published' => 1,
            'bareme' => 20,
            'coefficient' => 1,
            'titre' => 'Devoir 1',
        ]);
        $eval2 = ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $annee->id,
            'is_published' => 1,
            'bareme' => 20,
            'coefficient' => 1,
            'titre' => 'Examen 1',
        ]);

        $etudiant = ESBTPEtudiant::factory()->create([
            'matricule' => 'STU001',
            'nom' => 'KOFFI',
            'prenoms' => 'Marie',
        ]);
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        return compact('annee', 'classe', 'matiere', 'eval1', 'eval2', 'etudiant');
    }

    public function test_it_returns_avant_apres_for_matiere(): void
    {
        $this->authUser();
        $ctx = $this->makeContext();

        // Note actuelle eval1 = 12, eval2 = 14 → moyenne 13
        ESBTPNote::create([
            'evaluation_id' => $ctx['eval1']->id,
            'etudiant_id' => $ctx['etudiant']->id,
            'classe_id' => $ctx['classe']->id,
            'matiere_id' => $ctx['matiere']->id,
            'semestre' => 'semestre1',
            'annee_universitaire' => $ctx['annee']->name,
            'note' => 12,
            'is_absent' => 0,
        ]);
        ESBTPNote::create([
            'evaluation_id' => $ctx['eval2']->id,
            'etudiant_id' => $ctx['etudiant']->id,
            'classe_id' => $ctx['classe']->id,
            'matiere_id' => $ctx['matiere']->id,
            'semestre' => 'semestre1',
            'annee_universitaire' => $ctx['annee']->name,
            'note' => 14,
            'is_absent' => 0,
        ]);

        // On simule changer eval1 de 12 → 18
        $response = $this->postJson(route('esbtp.notes.preview-impact'), [
            'etudiant_id' => $ctx['etudiant']->id,
            'classe_id' => $ctx['classe']->id,
            'matiere_id' => $ctx['matiere']->id,
            'periode' => 'semestre1',
            'evaluation_id' => $ctx['eval1']->id,
            'hypothetical_note' => 18,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'matiere_avant' => 13.0,  // (12+14)/2
            'matiere_apres' => 16.0,  // (18+14)/2
        ]);
    }

    public function test_it_calculates_mention_change(): void
    {
        $this->authUser();
        $ctx = $this->makeContext();

        // Moyenne actuelle = 12.5 (Assez Bien)
        ESBTPNote::create([
            'evaluation_id' => $ctx['eval1']->id,
            'etudiant_id' => $ctx['etudiant']->id,
            'classe_id' => $ctx['classe']->id,
            'matiere_id' => $ctx['matiere']->id,
            'semestre' => 'semestre1',
            'annee_universitaire' => $ctx['annee']->name,
            'note' => 12.5,
            'is_absent' => 0,
        ]);

        // Hypothétique : si eval1 devient 11 → moyenne 11 (Passable)
        $response = $this->postJson(route('esbtp.notes.preview-impact'), [
            'etudiant_id' => $ctx['etudiant']->id,
            'classe_id' => $ctx['classe']->id,
            'matiere_id' => $ctx['matiere']->id,
            'periode' => 'semestre1',
            'evaluation_id' => $ctx['eval1']->id,
            'hypothetical_note' => 11,
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertSame('Assez Bien', $data['mention_avant']);
        $this->assertSame('Passable', $data['mention_apres']);
        $this->assertTrue($data['changed_mention']);
    }

    public function test_it_handles_first_note_no_existing_average(): void
    {
        $this->authUser();
        $ctx = $this->makeContext();

        // Aucune note existante
        $response = $this->postJson(route('esbtp.notes.preview-impact'), [
            'etudiant_id' => $ctx['etudiant']->id,
            'classe_id' => $ctx['classe']->id,
            'matiere_id' => $ctx['matiere']->id,
            'periode' => 'semestre1',
            'evaluation_id' => $ctx['eval1']->id,
            'hypothetical_note' => 15,
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertNull($data['matiere_avant']);
        $this->assertSame(15.0, (float) $data['matiere_apres']);
    }
}
