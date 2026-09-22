<?php

namespace Tests\Unit\Domain\Notes;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `notes:recompute` sans `--queue` — son mode PAR DÉFAUT — appelait
 * `handle()` sans le `NoteCalculationService` qu'il exige. Chaque combinaison
 * levait `ArgumentCountError`, que le `catch (\Throwable)` de la commande
 * transformait en une ligne « Erreur étudiant=… » : la commande tournait
 * jusqu'au bout sans jamais rien recalculer.
 *
 * Elle est la remédiation documentée des déplaceurs qui ne recalculent pas
 * d'eux-mêmes (`docs/api/CLI_COHERENCE_SYSTEME.md`) ; ce test garde qu'elle
 * fonctionne.
 */
class NotesRecomputeCommandTest extends TestCase
{
    use SchemaDesMoyennes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monterLeSchemaDesMoyennes();

        DB::table('esbtp_classes')->insert(['id' => 10, 'name' => 'BTS1', 'systeme_academique' => 'BTS']);
        DB::table('esbtp_matieres')->insert(['id' => 5, 'name' => 'Maths', 'unite_enseignement_id' => null, 'is_active' => 1]);
        $evaluationId = DB::table('esbtp_evaluations')->insertGetId([
            'titre' => 'Devoir', 'matiere_id' => 5, 'classe_id' => 10, 'annee_universitaire_id' => 1,
            'periode' => 'semestre1', 'status' => 'completed', 'bareme' => 20, 'coefficient' => 1,
        ]);
        DB::table('esbtp_notes')->insert([
            'evaluation_id' => $evaluationId, 'etudiant_id' => 100, 'matiere_id' => 5, 'classe_id' => 10,
            'note' => 13, 'is_absent' => 0,
        ]);
        // Moyenne périmée, telle qu'un déplacement non recalculé la laisse.
        DB::table('esbtp_resultats')->insert([
            'etudiant_id' => 100, 'classe_id' => 10, 'matiere_id' => 5, 'annee_universitaire_id' => 1,
            'periode' => 'semestre1', 'moyenne' => 4, 'coefficient' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        $this->demonterLeSchemaDesMoyennes();

        parent::tearDown();
    }

    public function test_le_mode_par_defaut_recalcule_reellement(): void
    {
        $this->artisan('notes:recompute', ['--classe' => 10])
            ->doesntExpectOutputToContain('Erreur')
            ->assertSuccessful();

        $this->assertSame(13.0, (float) DB::table('esbtp_resultats')->where('etudiant_id', 100)->value('moyenne'));
    }
}
