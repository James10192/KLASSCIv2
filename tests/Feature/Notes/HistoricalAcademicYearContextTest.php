<?php

namespace Tests\Feature\Notes;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HistoricalAcademicYearContextTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ESBTPAnneeUniversitaire $historique;
    private ESBTPAnneeUniversitaire $courante;
    private ESBTPClasse $classe;
    private ESBTPMatiere $matiere;
    private ESBTPEtudiant $ancienEtudiant;
    private ESBTPEtudiant $nouvelEtudiant;
    private ESBTPEvaluation $ancienneEvaluation;
    private ESBTPEvaluation $nouvelleEvaluation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            PaywallMiddleware::class,
            EnsureInstalled::class,
            CheckInstalled::class,
        ]);

        Role::findOrCreate('superAdmin', 'web');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('superAdmin');

        $this->historique = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026',
            'is_current' => false,
        ]);
        $this->courante = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2026-2027',
            'is_current' => true,
        ]);

        $this->classe = ESBTPClasse::factory()->create([
            'is_active' => true,
            'systeme_academique' => null,
        ]);
        $this->matiere = ESBTPMatiere::factory()->create([
            'unite_enseignement_id' => null,
        ]);

        $this->ancienEtudiant = $this->inscrire('HIST001', $this->historique);
        $this->nouvelEtudiant = $this->inscrire('CURR001', $this->courante);

        $this->ancienneEvaluation = $this->evaluation('Devoir historique', $this->historique, '2026-05-10 08:00:00');
        $this->nouvelleEvaluation = $this->evaluation('Devoir courant', $this->courante, '2026-10-10 08:00:00');
    }

    private function inscrire(string $matricule, ESBTPAnneeUniversitaire $annee): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create([
            'matricule' => $matricule,
            'nom' => $matricule,
            'prenoms' => 'Test',
        ]);

        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        return $etudiant;
    }

    private function evaluation(string $titre, ESBTPAnneeUniversitaire $annee, string $date): ESBTPEvaluation
    {
        return ESBTPEvaluation::factory()->create([
            'titre' => $titre,
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->matiere->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'type' => 'devoir',
            'date_evaluation' => $date,
            'status' => ESBTPEvaluation::STATUS_COMPLETED,
            'is_published' => true,
            'bareme' => 20,
            'coefficient' => 1,
        ]);
    }

    public function test_les_apis_notes_acceptent_une_annee_locale_et_gardent_is_current_comme_fallback(): void
    {
        $this->actingAs($this->admin);

        $historique = $this->getJson(route('esbtp.notes.evaluations.by-class-matiere', [
            'classId' => $this->classe->id,
            'matiereId' => $this->matiere->id,
            'annee_universitaire_id' => $this->historique->id,
        ]))->assertOk();

        $this->assertSame(
            [$this->ancienneEvaluation->id],
            collect($historique->json('evaluations'))->pluck('id')->values()->all()
        );

        $courant = $this->getJson(route('esbtp.notes.evaluations.by-class-matiere', [
            'classId' => $this->classe->id,
            'matiereId' => $this->matiere->id,
        ]))->assertOk();

        $this->assertSame(
            [$this->nouvelleEvaluation->id],
            collect($courant->json('evaluations'))->pluck('id')->values()->all()
        );

        $etudiantsHistoriques = $this->getJson(route('esbtp.notes.classes.students', [
            'classe' => $this->classe->id,
            'annee_universitaire_id' => $this->historique->id,
        ]))->assertOk();

        $this->assertContains($this->ancienEtudiant->id, collect($etudiantsHistoriques->json('students'))->pluck('id')->all());
        $this->assertNotContains($this->nouvelEtudiant->id, collect($etudiantsHistoriques->json('students'))->pluck('id')->all());

        $etudiantsCourants = $this->getJson(route('esbtp.notes.classes.students', [
            'classe' => $this->classe->id,
        ]))->assertOk();

        $this->assertContains($this->nouvelEtudiant->id, collect($etudiantsCourants->json('students'))->pluck('id')->all());
        $this->assertNotContains($this->ancienEtudiant->id, collect($etudiantsCourants->json('students'))->pluck('id')->all());
    }

    public function test_creer_une_evaluation_historique_ne_change_pas_l_annee_courante(): void
    {
        $this->actingAs($this->admin);

        $payload = [
            'titre' => 'Examen de clôture 2025-2026',
            'type' => 'devoir',
            'date_evaluation' => '2026-06-20',
            'heure_debut' => '08:00',
            'heure_fin' => '09:00',
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->matiere->id,
            'bareme' => 20,
            'coefficient' => 1,
            'periode' => 'semestre1',
            'is_published' => 1,
            'annee_universitaire_id' => $this->historique->id,
        ];

        $this->post(route('esbtp.evaluations.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('esbtp_evaluations', [
            'titre' => 'Examen de clôture 2025-2026',
            'annee_universitaire_id' => $this->historique->id,
        ]);
        $this->assertTrue($this->courante->fresh()->is_current);

        unset($payload['annee_universitaire_id']);
        $payload['titre'] = 'Évaluation sans filtre';

        $this->post(route('esbtp.evaluations.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('esbtp_evaluations', [
            'titre' => 'Évaluation sans filtre',
            'annee_universitaire_id' => $this->courante->id,
        ]);
    }
}
