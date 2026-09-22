<?php

namespace Tests\Unit\Domain\Notes;

use App\Models\ESBTPNote;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Supprimer la dernière note d'un élève dans une matière réveille
 * l'observateur, qui relance le recalcul. Il ne reste aucune note :
 * `studentMatiereAverage([])` rend 0.0, et le job l'écrivait — 0/20 au
 * bulletin pour une matière où l'élève n'a simplement plus de note.
 */
class SuppressionDeLaDerniereNoteTest extends TestCase
{
    use SchemaDesMoyennes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monterLeSchemaDesMoyennes();

        DB::table('esbtp_classes')->insert(['id' => 10, 'name' => 'BTS1', 'systeme_academique' => 'BTS']);
        DB::table('esbtp_matieres')->insert(['id' => 5, 'name' => 'Maths', 'unite_enseignement_id' => null, 'is_active' => 1]);
    }

    protected function tearDown(): void
    {
        $this->demonterLeSchemaDesMoyennes();

        parent::tearDown();
    }

    public function test_supprimer_la_derniere_note_n_ecrit_pas_zero(): void
    {
        $evaluationId = DB::table('esbtp_evaluations')->insertGetId([
            'titre' => 'Devoir', 'matiere_id' => 5, 'classe_id' => 10, 'annee_universitaire_id' => 1,
            'periode' => 'semestre1', 'status' => 'completed', 'bareme' => 20, 'coefficient' => 1,
        ]);
        $noteId = DB::table('esbtp_notes')->insertGetId([
            'evaluation_id' => $evaluationId, 'etudiant_id' => 100, 'matiere_id' => 5, 'classe_id' => 10,
            'note' => 14, 'is_absent' => 0,
        ]);
        DB::table('esbtp_resultats')->insert([
            'etudiant_id' => 100, 'classe_id' => 10, 'matiere_id' => 5, 'annee_universitaire_id' => 1,
            'periode' => 'semestre1', 'moyenne' => 14, 'coefficient' => 1,
        ]);

        ESBTPNote::findOrFail($noteId)->delete();

        // La ligne reste, sans être remise à zéro — et sans trace de recalcul.
        $this->assertSame(14.0, (float) DB::table('esbtp_resultats')->where('matiere_id', 5)->value('moyenne'));
        $this->assertDatabaseCount('esbtp_resultats_recompute_log', 0);
    }
}
