<?php

namespace Tests\Feature\API\CLI;

use App\Http\Controllers\API\CLI\CLIEvaluationCoverageController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EvaluationCoverageCliTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function cli_route_is_registered(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->uri() === 'api/cli/evaluations/coverage')
            ->map(fn ($route) => $route->methods());

        $this->assertTrue($routes->flatten()->contains('GET'));
    }

    /** @test */
    public function coverage_requires_cli_read_ability(): void
    {
        $response = app(CLIEvaluationCoverageController::class)->index(
            $this->requestWithAbilities([], [])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    /** @test */
    public function coverage_groups_first_year_bts_by_filiere_and_counts_students_with_notes(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $niveau1 = ESBTPNiveauEtude::factory()->create(['type' => 'BTS', 'year' => 1, 'name' => 'Première Année BTS']);
        $niveau2 = ESBTPNiveauEtude::factory()->create(['type' => 'BTS', 'year' => 2, 'name' => 'Deuxième Année BTS']);

        $batiment = ESBTPFiliere::factory()->create(['name' => 'BATIMENT']);
        $tp = ESBTPFiliere::factory()->create(['name' => 'Travaux Publics']);

        $classeBat = ESBTPClasse::factory()->create([
            'name' => '1BTS GBAT A',
            'filiere_id' => $batiment->id,
            'niveau_etude_id' => $niveau1->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'BTS',
        ]);
        $classeTp = ESBTPClasse::factory()->create([
            'name' => '1BTS GTP A',
            'filiere_id' => $tp->id,
            'niveau_etude_id' => $niveau1->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'BTS',
        ]);
        $classeAnnee2 = ESBTPClasse::factory()->create([
            'name' => '2BTS GBAT A',
            'filiere_id' => $batiment->id,
            'niveau_etude_id' => $niveau2->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'BTS',
        ]);

        $maths = ESBTPMatiere::factory()->create(['name' => 'Mathématiques', 'unite_enseignement_id' => null]);
        $fr = ESBTPMatiere::factory()->create(['name' => 'Français', 'unite_enseignement_id' => null]);

        $etudiantsBat = ESBTPEtudiant::factory()->count(3)->create();
        foreach ($etudiantsBat as $etudiant) {
            ESBTPInscription::factory()->create([
                'etudiant_id' => $etudiant->id,
                'classe_id' => $classeBat->id,
                'filiere_id' => $batiment->id,
                'niveau_id' => $niveau1->id,
                'annee_universitaire_id' => $annee->id,
                'status' => 'active',
                'workflow_step' => 'etudiant_cree',
            ]);
        }

        $etudiantTp = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiantTp->id,
            'classe_id' => $classeTp->id,
            'filiere_id' => $tp->id,
            'niveau_id' => $niveau1->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        $etudiantAnnee2 = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiantAnnee2->id,
            'classe_id' => $classeAnnee2->id,
            'filiere_id' => $batiment->id,
            'niveau_id' => $niveau2->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        $evalMaths = ESBTPEvaluation::factory()->create([
            'titre' => 'Devoir Maths 1',
            'type' => 'devoir',
            'matiere_id' => $maths->id,
            'classe_id' => $classeBat->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'status' => 'completed',
        ]);
        $evalFr = ESBTPEvaluation::factory()->create([
            'titre' => 'Devoir Français 1',
            'type' => 'devoir',
            'matiere_id' => $fr->id,
            'classe_id' => $classeBat->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'status' => 'completed',
        ]);
        $evalTp = ESBTPEvaluation::factory()->create([
            'titre' => 'Devoir Maths TP',
            'type' => 'devoir',
            'matiere_id' => $maths->id,
            'classe_id' => $classeTp->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'status' => 'completed',
        ]);
        $evalAnnee2 = ESBTPEvaluation::factory()->create([
            'titre' => 'Devoir Maths 2A',
            'type' => 'devoir',
            'matiere_id' => $maths->id,
            'classe_id' => $classeAnnee2->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'status' => 'completed',
        ]);
        $evalCancelled = ESBTPEvaluation::factory()->create([
            'titre' => 'Devoir annulé',
            'type' => 'devoir',
            'matiere_id' => $maths->id,
            'classe_id' => $classeBat->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'status' => ESBTPEvaluation::STATUS_CANCELLED,
        ]);

        $this->noter($etudiantsBat[0], $evalMaths, 12);
        $this->noter($etudiantsBat[1], $evalMaths, 14);
        $this->noter($etudiantsBat[2], $evalMaths, 0, true);
        $this->noter($etudiantsBat[0], $evalFr, 10);
        $this->noter($etudiantTp, $evalTp, 11);
        $this->noter($etudiantAnnee2, $evalAnnee2, 16);
        $this->noter($etudiantsBat[0], $evalCancelled, 18);

        $response = app(CLIEvaluationCoverageController::class)->index(
            $this->requestWithAbilities(['cli:read'], [])
        );

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true)['data'];

        $this->assertSame(2, $payload['summary']['filieres']);
        $this->assertSame(2, $payload['summary']['classes']);
        $this->assertSame(4, $payload['summary']['effectif']);
        $this->assertSame(3, $payload['summary']['evaluations']);

        $byName = collect($payload['filieres'])->keyBy('filiere');
        $this->assertTrue($byName->has('BATIMENT'));
        $this->assertTrue($byName->has('Travaux Publics'));

        $bat = $byName['BATIMENT'];
        $this->assertSame(3, $bat['effectif']);
        $this->assertCount(2, $bat['matieres']);

        $mathsBat = collect($bat['matieres'])->firstWhere('matiere', 'Mathématiques');
        $this->assertSame(2, $mathsBat['etudiants_avec_note']);
        $this->assertSame(3, $mathsBat['effectif']);
        $this->assertSame(66.7, $mathsBat['couverture_pct']);
        $this->assertCount(1, $mathsBat['evaluations']);
        $this->assertSame('Devoir Maths 1', $mathsBat['evaluations'][0]['titre']);
        $this->assertSame(2, $mathsBat['evaluations'][0]['nb_numeric']);
        $this->assertSame(1, $mathsBat['evaluations'][0]['nb_absents']);

        $frBat = collect($bat['matieres'])->firstWhere('matiere', 'Français');
        $this->assertSame(1, $frBat['etudiants_avec_note']);

        $tpRow = $byName['Travaux Publics'];
        $this->assertSame(1, $tpRow['effectif']);
        $mathsTp = collect($tpRow['matieres'])->firstWhere('matiere', 'Mathématiques');
        $this->assertSame(1, $mathsTp['etudiants_avec_note']);
        $this->assertSame(100.0, $mathsTp['couverture_pct']);
    }

    private function requestWithAbilities(array $abilities, array $query): Request
    {
        $user = new User();
        $user->id = 1;
        $request = Request::create('/api/cli/evaluations/coverage', 'GET', $query);
        $request->setUserResolver(fn () => new class($user, $abilities) {
            public function __construct(private User $user, private array $abilities) {}

            public function tokenCan(string $ability): bool
            {
                return in_array($ability, $this->abilities, true);
            }
        });

        return $request;
    }

    private function noter(ESBTPEtudiant $etudiant, ESBTPEvaluation $evaluation, float $note, bool $absent = false): void
    {
        ESBTPNote::create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $evaluation->matiere_id,
            'classe_id' => $evaluation->classe_id,
            'note' => $absent ? 0 : $note,
            'is_absent' => $absent,
        ]);
    }
}
