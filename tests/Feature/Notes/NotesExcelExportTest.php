<?php

namespace Tests\Feature\Notes;

use App\Exports\NotesClasseMatiereExport;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotesExcelExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Permissions
        Permission::findOrCreate('notes.view', 'web');
        Permission::findOrCreate('module.notes_evaluations.access', 'web');
        Permission::findOrCreate('admin.access', 'web');
    }

    private function authUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('notes.view');
        $user->givePermissionTo('module.notes_evaluations.access');
        $user->givePermissionTo('admin.access');
        $this->actingAs($user);

        return $user;
    }

    private function buildContext(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();

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
            'bareme' => 30,
            'coefficient' => 2,
            'titre' => 'Examen 1',
        ]);

        return compact('annee', 'classe', 'matiere', 'eval1', 'eval2');
    }

    private function makeStudent(int $classeId, int $anneeId, string $matricule = 'STU001'): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create([
            'matricule' => $matricule,
            'nom' => 'NOM',
            'prenoms' => 'Prenom',
        ]);
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classeId,
            'annee_universitaire_id' => $anneeId,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        return $etudiant;
    }

    public function test_it_exports_classe_matiere_to_excel(): void
    {
        $this->authUser();
        $ctx = $this->buildContext();
        $student = $this->makeStudent($ctx['classe']->id, $ctx['annee']->id);

        ESBTPNote::create([
            'evaluation_id' => $ctx['eval1']->id,
            'etudiant_id' => $student->id,
            'classe_id' => $ctx['classe']->id,
            'matiere_id' => $ctx['matiere']->id,
            'semestre' => 'semestre1',
            'annee_universitaire' => $ctx['annee']->name,
            'note' => 15,
            'is_absent' => 0,
        ]);

        Excel::fake();

        $response = $this->get(route('esbtp.notes.export-excel', [
            'classe' => $ctx['classe']->id,
            'matiere' => $ctx['matiere']->id,
            'periode' => 'semestre1',
        ]));

        $response->assertOk();
        Excel::assertDownloaded('/^notes_/');
    }

    public function test_it_includes_correct_headers_with_bareme(): void
    {
        $this->authUser();
        $ctx = $this->buildContext();
        $this->makeStudent($ctx['classe']->id, $ctx['annee']->id);

        $export = new NotesClasseMatiereExport(
            $ctx['classe']->id,
            $ctx['matiere']->id,
            'semestre1',
            $ctx['annee']->id
        );

        $headings = $export->headings();
        $this->assertSame('Matricule', $headings[0]);
        $this->assertSame('Nom & Prénoms', $headings[1]);
        $this->assertStringContainsString('/20', $headings[2]);  // eval1
        $this->assertStringContainsString('/30', $headings[3]);  // eval2 bareme 30
        $this->assertStringContainsString('×2', $headings[3]);   // eval2 coef 2
        $this->assertSame('Moyenne /20', end($headings));
    }

    public function test_it_returns_422_when_volume_exceeds_5000_cells(): void
    {
        $this->authUser();
        $ctx = $this->buildContext();

        // 1 évaluation existe déjà via buildContext (eval1 + eval2 = 2 évals)
        // Pour dépasser 5000 cellules, créer 2501 étudiants → 2501×2 = 5002
        for ($i = 0; $i < 2501; $i++) {
            $this->makeStudent($ctx['classe']->id, $ctx['annee']->id, 'BULK' . $i);
        }

        $response = $this->get(route('esbtp.notes.export-excel', [
            'classe' => $ctx['classe']->id,
            'matiere' => $ctx['matiere']->id,
            'periode' => 'semestre1',
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_it_throttles_export_at_10_per_minute(): void
    {
        $this->authUser();
        $ctx = $this->buildContext();
        $this->makeStudent($ctx['classe']->id, $ctx['annee']->id);

        Excel::fake();

        // 10 requêtes OK
        for ($i = 0; $i < 10; $i++) {
            $r = $this->get(route('esbtp.notes.export-excel', [
                'classe' => $ctx['classe']->id,
                'matiere' => $ctx['matiere']->id,
                'periode' => 'semestre1',
            ]));
            $this->assertContains($r->status(), [200, 429], "Iteration {$i}: status was {$r->status()}");
        }

        // 11ème devrait être 429
        $r11 = $this->get(route('esbtp.notes.export-excel', [
            'classe' => $ctx['classe']->id,
            'matiere' => $ctx['matiere']->id,
            'periode' => 'semestre1',
        ]));

        $this->assertSame(429, $r11->status(), 'La 11ème requête devrait être throttle 429.');
    }
}
