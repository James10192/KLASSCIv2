<?php

namespace Tests\Unit\Domain\Notes;

use App\Models\ESBTPNote;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Supprimer la dernière note d'un élève dans une matière réveille
 * l'observateur, qui relance le recalcul. Il ne reste aucune note, et le job
 * écrivait 0/20 : au bulletin, pour une matière où l'élève n'a simplement plus
 * de note. Le garde du déplacement (`PerimetreDeRecalcul::recalculerUnCouple()`)
 * ne voit pas ce chemin-là.
 *
 * Marquer la dernière note absente, lui, n'est PAS couvert, et c'est voulu : la
 * décision écrite dans `PerimetreDeRecalcul` est que ce geste de l'enseignant
 * fixe la moyenne. Le second test la verrouille, pour qu'on ne l'inverse pas
 * par accident.
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
        $noteId = $this->uneNoteAQuatorze();

        ESBTPNote::findOrFail($noteId)->delete();

        // La ligne reste, sans être remise à zéro — et sans trace de recalcul.
        $this->assertSame(14.0, (float) DB::table('esbtp_resultats')->where('matiere_id', 5)->value('moyenne'));
        $this->assertDatabaseCount('esbtp_resultats_recompute_log', 0);
    }

    public function test_passer_la_derniere_note_en_absence_fixe_toujours_la_moyenne(): void
    {
        $note = ESBTPNote::findOrFail($this->uneNoteAQuatorze());
        $note->is_absent = true;
        $note->save();

        $this->assertSame(0.0, (float) DB::table('esbtp_resultats')->where('matiere_id', 5)->value('moyenne'));
    }

    private function uneNoteAQuatorze(): int
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

        return $noteId;
    }
}
