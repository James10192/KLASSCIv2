<?php

namespace Tests\Feature\Evaluations;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * GET /api/cli/evaluations/notes : lecture seule des notes saisies.
 */
class LectureNotesCliTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Sans instance installee, CheckInstalled/EnsureInstalled redirigent (302).
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
    }

    public function test_renvoie_les_notes_de_l_evaluation_sans_rien_modifier(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:read']);

        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $evaluation = ESBTPEvaluation::factory()->create(['annee_universitaire_id' => $annee->id, 'status' => ESBTPEvaluation::STATUS_COMPLETED]);
        $etudiant = ESBTPEtudiant::factory()->create();
        ESBTPNote::factory()->create(['evaluation_id' => $evaluation->id, 'etudiant_id' => $etudiant->id, 'note' => 0]);

        $avant = ESBTPNote::count();

        $this->getJson('/api/cli/evaluations/notes?annee_id='.$annee->id.'&evaluation_id='.$evaluation->id)
            ->assertOk()
            ->assertJsonPath('data.nb_notes', 1)
            ->assertJsonPath('data.evaluations.0.notes.0.etudiant_id', $etudiant->id)
            ->assertJsonPath('data.evaluations.0.notes.0.note', 0.0);

        $this->assertSame($avant, ESBTPNote::count());
    }

    public function test_une_evaluation_annulee_n_est_pas_rendue(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:read']);

        $annee = ESBTPAnneeUniversitaire::factory()->create();
        ESBTPEvaluation::factory()->create(['annee_universitaire_id' => $annee->id, 'status' => ESBTPEvaluation::STATUS_CANCELLED]);

        $this->getJson('/api/cli/evaluations/notes?annee_id='.$annee->id)
            ->assertOk()
            ->assertJsonPath('data.nb_evaluations', 0);
    }
}
