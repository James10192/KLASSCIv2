<?php

namespace Tests\Unit\Domain\Notes;

use App\Domain\BtsTroncCommun\ClasseOuvertureResolver;
use App\Domain\Notes\Actions\DeplacerUneEvaluation;
use App\Http\Controllers\API\CLI\CLIEvaluationDeplacementController;
use App\Http\Controllers\API\CLI\CLIEvaluationPeriodeController;
use App\Models\ESBTPEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Les trois déplaceurs qui changent une évaluation de coordonnée entre deux
 * positions COHÉRENTES : l'écran d'édition, et les deux réparations CLI de
 * période. Chacun doit recalculer la moyenne rejointe et mettre de côté celle
 * qu'il vide — laissée en place, elle compterait ses notes deux fois.
 *
 * Même situation pour les trois : une note de 14 au semestre 1, déjà
 * enregistrée ; l'évaluation passe au semestre 2.
 */
class DeplaceursBranchesTest extends TestCase
{
    use SchemaDesMoyennes;

    private int $evaluation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monterLeSchemaDesMoyennes();

        DB::table('esbtp_classes')->insert(['id' => 10, 'name' => 'BTS2 Bâtiment', 'systeme_academique' => 'BTS']);
        DB::table('esbtp_matieres')->insert(['id' => 5, 'name' => 'Maths', 'unite_enseignement_id' => null, 'is_active' => 1]);
        $this->evaluation = DB::table('esbtp_evaluations')->insertGetId([
            'titre' => 'Devoir', 'matiere_id' => 5, 'classe_id' => 10, 'annee_universitaire_id' => 1,
            'periode' => 'semestre1', 'status' => 'completed', 'bareme' => 20, 'coefficient' => 1,
        ]);
        DB::table('esbtp_notes')->insert([
            'evaluation_id' => $this->evaluation, 'etudiant_id' => 100, 'matiere_id' => 5, 'classe_id' => 10,
            'note' => 14, 'is_absent' => 0,
        ]);
        DB::table('esbtp_resultats')->insert([
            'etudiant_id' => 100, 'classe_id' => 10, 'matiere_id' => 5, 'annee_universitaire_id' => 1,
            'periode' => 'semestre1', 'moyenne' => 14, 'coefficient' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        $this->demonterLeSchemaDesMoyennes();

        parent::tearDown();
    }

    public function test_l_ecran_d_edition_d_une_evaluation(): void
    {
        $evaluation = ESBTPEvaluation::findOrFail($this->evaluation);
        $evaluation->periode = 'semestre2';

        $rapport = app(DeplacerUneEvaluation::class)->enregistrer($evaluation);

        $this->assertCount(1, $rapport['lignes_retirees']);
        $this->assertMoyennesSuivies();
    }

    public function test_le_deplacement_cli_de_semestre(): void
    {
        $reponse = app(CLIEvaluationDeplacementController::class)->deplacer($this->requeteCli([
            'evaluation_ids' => [$this->evaluation], 'periode' => 'semestre2', 'dry_run' => false,
        ]));

        $this->assertCount(1, $reponse->getData(true)['data']['lignes_retirees']);
        $this->assertMoyennesSuivies();
    }

    public function test_la_reparation_cli_des_periodes_de_specialite(): void
    {
        // La classe n'ouvre qu'au semestre 2 : l'évaluation du semestre 1 y est une anomalie.
        DB::table('esbtp_classe_orientation_targets')->insert(['target_classe_id' => 10, 'semestre_activation' => 2, 'is_active' => 1]);
        app(ClasseOuvertureResolver::class)->oublier();

        $reponse = app(CLIEvaluationPeriodeController::class)->repair($this->requeteCli(['dry_run' => false]));

        $this->assertCount(1, $reponse->getData(true)['data']['lignes_retirees']);
        $this->assertMoyennesSuivies();
    }

    private function assertMoyennesSuivies(): void
    {
        // Le semestre 1 n'a plus de note : sa ligne est mise de côté, pas remise à zéro.
        $this->assertNotNull(DB::table('esbtp_resultats')->where('periode', 'semestre1')->value('deleted_at'));
        // Le semestre 2 porte la note, et sa moyenne est enregistrée.
        $this->assertSame(14.0, (float) DB::table('esbtp_resultats')->where('periode', 'semestre2')->whereNull('deleted_at')->value('moyenne'));
    }

    /** Une requête portée par un jeton `cli:admin`, sans Sanctum ni table d'utilisateurs. */
    private function requeteCli(array $corps): Request
    {
        $requete = Request::create('/api/cli/test', 'POST', $corps);
        $requete->setUserResolver(fn () => new class
        {
            public int $id = 1;

            public function tokenCan(string $capacite): bool
            {
                return true;
            }
        });

        return $requete;
    }
}
