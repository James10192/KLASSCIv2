<?php

namespace Tests\Feature\Evaluations;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Endpoint CLI POST /api/cli/evaluations/creer : simulation, confirmation,
 * doublons, lot atomique et règle des régularisations.
 */
class CreationEvaluationCliTest extends TestCase
{
    use DatabaseTransactions;

    private const URL = '/api/cli/evaluations/creer';

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPClasse $classe;

    private ESBTPMatiere $matiere;

    protected function setUp(): void
    {
        parent::setUp();

        $this->annee = ESBTPAnneeUniversitaire::factory()->create();
        $this->classe = ESBTPClasse::factory()->create(['systeme_academique' => 'BTS', 'is_active' => true]);
        $this->matiere = ESBTPMatiere::factory()->create();

        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);
    }

    private function ligne(array $surcharge = []): array
    {
        return array_merge([
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->matiere->id,
            'titre' => 'Devoir 1',
            'type' => 'devoir',
            'periode' => 'semestre1',
            'date_evaluation' => '2026-04-30',
            'heure_debut' => '08:00',
            'heure_fin' => '10:00',
            'bareme' => 20,
            'coefficient' => 1,
            'is_published' => false,
        ], $surcharge);
    }

    private function payload(array $entries, array $supplement = []): array
    {
        return array_merge([
            'annee_universitaire_id' => $this->annee->id,
            'motif' => 'Création des évaluations de session normale',
            'entries' => $entries,
        ], $supplement);
    }

    public function test_la_simulation_par_defaut_ne_cree_rien(): void
    {
        $avant = ESBTPEvaluation::count();

        $this->postJson(self::URL, $this->payload([$this->ligne()]))
            ->assertOk()
            ->assertJsonPath('data.dry_run', true);

        $this->assertSame($avant, ESBTPEvaluation::count());
    }

    public function test_l_execution_sans_confirmation_est_refusee(): void
    {
        $avant = ESBTPEvaluation::count();

        $this->postJson(self::URL, $this->payload([$this->ligne()], ['dry_run' => false]))
            ->assertStatus(422);

        $this->assertSame($avant, ESBTPEvaluation::count());
    }

    public function test_l_execution_confirmee_cree_une_evaluation_brouillon(): void
    {
        $this->postJson(self::URL, $this->payload([$this->ligne()], [
            'dry_run' => false,
            'confirmation' => 'CREER_LES_EVALUATIONS',
        ]))->assertOk();

        $evaluation = ESBTPEvaluation::where('titre', 'Devoir 1')->firstOrFail();
        $this->assertSame(ESBTPEvaluation::STATUS_DRAFT, $evaluation->status);
        $this->assertSame(120, (int) $evaluation->duree_minutes);
    }

    public function test_un_doublon_du_meme_jour_en_base_est_refuse(): void
    {
        $this->postJson(self::URL, $this->payload([$this->ligne()], [
            'dry_run' => false,
            'confirmation' => 'CREER_LES_EVALUATIONS',
        ]))->assertOk();

        $avant = ESBTPEvaluation::count();

        $this->postJson(self::URL, $this->payload([$this->ligne()]))
            ->assertStatus(422);

        $this->assertSame($avant, ESBTPEvaluation::count());
    }

    public function test_un_lot_contenant_un_doublon_ne_cree_aucune_ligne(): void
    {
        $avant = ESBTPEvaluation::count();

        $this->postJson(self::URL, $this->payload([
            $this->ligne(['titre' => 'Devoir A']),
            $this->ligne(['titre' => 'Devoir A']),
        ], ['dry_run' => false, 'confirmation' => 'CREER_LES_EVALUATIONS']))
            ->assertStatus(422);

        $this->assertSame($avant, ESBTPEvaluation::count());
    }

    public function test_un_titre_de_regularisation_est_refuse(): void
    {
        $avant = ESBTPEvaluation::count();

        $this->postJson(self::URL, $this->payload([$this->ligne(['titre' => 'Régularisation Maths'])]))
            ->assertStatus(422);

        $this->assertSame($avant, ESBTPEvaluation::count());
    }
}
