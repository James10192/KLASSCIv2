<?php

namespace Tests\Feature;

use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Couvre :
 *  - dispatch du job sur saved/deleted
 *  - calcul correct de la moyenne pondérée (normalisation barème)
 *  - écriture dans esbtp_resultats_recompute_log
 *  - no-op gracieux si évaluation manquante
 */
class RecomputeStudentResultatTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPClasse $classe;

    private ESBTPMatiere $matiere;

    private ESBTPEtudiant $etudiant;

    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->annee = ESBTPAnneeUniversitaire::create([
            'name' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_active' => true,
            'is_current' => true,
        ]);

        $this->classe = ESBTPClasse::create([
            'name' => 'BTS1 Test',
            'code' => 'BTS1T',
            'places_totales' => 30,
            'places_occupees' => 0,
            'is_active' => true,
        ]);

        $this->matiere = ESBTPMatiere::create([
            'name' => 'Mathématiques',
            'code' => 'MATH',
            'is_active' => true,
        ]);

        $this->etudiant = ESBTPEtudiant::create([
            'matricule' => 'TEST001',
            'nom' => 'Student',
            'prenoms' => 'Test',
        ]);
    }

    private function makeEvaluation(float $bareme = 20, float $coefficient = 1, string $periode = 'semestre1'): ESBTPEvaluation
    {
        return ESBTPEvaluation::create([
            'titre' => 'Devoir test',
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'type' => 'devoir',
            'date_evaluation' => now(),
            'coefficient' => $coefficient,
            'bareme' => $bareme,
            'periode' => $periode,
            'annee_universitaire_id' => $this->annee->id,
            'status' => 'active',
        ]);
    }

    public function test_it_dispatches_job_when_note_saved(): void
    {
        Queue::fake();

        $eval = $this->makeEvaluation();

        ESBTPNote::create([
            'evaluation_id' => $eval->id,
            'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'note' => 15,
            'is_absent' => 0,
        ]);

        Queue::assertPushed(RecomputeStudentResultatJob::class, function ($job) {
            return $job->etudiantId === $this->etudiant->id
                && $job->matiereId === $this->matiere->id
                && $job->source === 'observer';
        });
    }

    public function test_it_dispatches_job_when_note_deleted(): void
    {
        $eval = $this->makeEvaluation();

        $note = ESBTPNote::create([
            'evaluation_id' => $eval->id,
            'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'note' => 12,
            'is_absent' => 0,
        ]);

        Queue::fake();

        $note->delete();

        Queue::assertPushed(RecomputeStudentResultatJob::class);
    }

    public function test_it_recomputes_resultat_correctly(): void
    {
        // Eval 1 : note 15/20 coef 2  → 15 * 2 = 30
        // Eval 2 : note 10/20 coef 1  → 10 * 1 = 10
        // SUM coef = 3, SUM points = 40 → moyenne = 13.33
        $eval1 = $this->makeEvaluation(bareme: 20, coefficient: 2);
        $eval2 = $this->makeEvaluation(bareme: 20, coefficient: 1);

        // On insert sans dispatch (mute) puis on appelle le Job manuellement
        \App\Observers\ESBTPNoteObserver::$muted = true;

        ESBTPNote::create([
            'evaluation_id' => $eval1->id,
            'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'note' => 15,
            'is_absent' => 0,
        ]);

        ESBTPNote::create([
            'evaluation_id' => $eval2->id,
            'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'note' => 10,
            'is_absent' => 0,
        ]);

        \App\Observers\ESBTPNoteObserver::$muted = false;

        (new RecomputeStudentResultatJob(
            etudiantId: $this->etudiant->id,
            classeId: $this->classe->id,
            matiereId: $this->matiere->id,
            anneeUniversitaireId: $this->annee->id,
            periode: 'semestre1',
            source: 'manual',
        ))->handle();

        $resultat = ESBTPResultat::where('etudiant_id', $this->etudiant->id)
            ->where('matiere_id', $this->matiere->id)
            ->where('periode', 'semestre1')
            ->first();

        $this->assertNotNull($resultat);
        $this->assertEqualsWithDelta(13.33, (float) $resultat->moyenne, 0.01);
    }

    public function test_it_normalizes_note_via_bareme(): void
    {
        // Eval barème 40, note 30/40 → 15/20
        $eval = $this->makeEvaluation(bareme: 40, coefficient: 1);

        \App\Observers\ESBTPNoteObserver::$muted = true;

        ESBTPNote::create([
            'evaluation_id' => $eval->id,
            'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'note' => 30,
            'is_absent' => 0,
        ]);

        \App\Observers\ESBTPNoteObserver::$muted = false;

        (new RecomputeStudentResultatJob(
            etudiantId: $this->etudiant->id,
            classeId: $this->classe->id,
            matiereId: $this->matiere->id,
            anneeUniversitaireId: $this->annee->id,
            periode: 'semestre1',
        ))->handle();

        $resultat = ESBTPResultat::where('etudiant_id', $this->etudiant->id)->first();

        $this->assertEqualsWithDelta(15.00, (float) $resultat->moyenne, 0.01);
    }

    public function test_it_excludes_absent_notes_from_calculation(): void
    {
        $eval1 = $this->makeEvaluation(bareme: 20, coefficient: 1);
        $eval2 = $this->makeEvaluation(bareme: 20, coefficient: 1);

        \App\Observers\ESBTPNoteObserver::$muted = true;

        ESBTPNote::create([
            'evaluation_id' => $eval1->id,
            'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'note' => 16,
            'is_absent' => 0,
        ]);

        ESBTPNote::create([
            'evaluation_id' => $eval2->id,
            'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'note' => 0,
            'is_absent' => 1,
        ]);

        \App\Observers\ESBTPNoteObserver::$muted = false;

        (new RecomputeStudentResultatJob(
            etudiantId: $this->etudiant->id,
            classeId: $this->classe->id,
            matiereId: $this->matiere->id,
            anneeUniversitaireId: $this->annee->id,
            periode: 'semestre1',
        ))->handle();

        $resultat = ESBTPResultat::where('etudiant_id', $this->etudiant->id)->first();

        // Seule la note 16 compte (absent exclu)
        $this->assertEqualsWithDelta(16.00, (float) $resultat->moyenne, 0.01);
    }

    public function test_it_logs_recompute_to_audit_table(): void
    {
        $eval = $this->makeEvaluation();

        \App\Observers\ESBTPNoteObserver::$muted = true;

        ESBTPNote::create([
            'evaluation_id' => $eval->id,
            'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'note' => 14,
            'is_absent' => 0,
        ]);

        \App\Observers\ESBTPNoteObserver::$muted = false;

        $this->assertSame(0, DB::table('esbtp_resultats_recompute_log')->count());

        (new RecomputeStudentResultatJob(
            etudiantId: $this->etudiant->id,
            classeId: $this->classe->id,
            matiereId: $this->matiere->id,
            anneeUniversitaireId: $this->annee->id,
            periode: 'semestre1',
            source: 'command',
        ))->handle();

        $logs = DB::table('esbtp_resultats_recompute_log')->get();
        $this->assertCount(1, $logs);

        $log = $logs->first();
        $this->assertSame($this->etudiant->id, (int) $log->etudiant_id);
        $this->assertSame($this->matiere->id, (int) $log->matiere_id);
        $this->assertSame('command', $log->source);
        $this->assertEqualsWithDelta(14.00, (float) $log->moyenne_apres, 0.01);
    }

    public function test_it_does_not_throw_when_evaluation_missing(): void
    {
        // Pas d'évaluation, pas de note — Job doit no-op gracieusement
        $this->expectNotToPerformAssertions();

        (new RecomputeStudentResultatJob(
            etudiantId: $this->etudiant->id,
            classeId: $this->classe->id,
            matiereId: $this->matiere->id,
            anneeUniversitaireId: $this->annee->id,
            periode: 'semestre1',
        ))->handle();
    }

    public function test_observer_muted_does_not_dispatch(): void
    {
        Queue::fake();

        \App\Observers\ESBTPNoteObserver::$muted = true;

        $eval = $this->makeEvaluation();

        ESBTPNote::create([
            'evaluation_id' => $eval->id,
            'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'note' => 11,
            'is_absent' => 0,
        ]);

        \App\Observers\ESBTPNoteObserver::$muted = false;

        Queue::assertNothingPushed();
    }
}
