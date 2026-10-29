<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPNote;
use App\Models\ESBTPPlanificationAcademique;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicMetricInvalidationSourcesTest extends AcademicPilotageDatabaseTestCase
{
    private AcademicMetricSnapshotInvalidationService $invalidation;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('filiere_id')->nullable();
            $table->unsignedBigInteger('niveau_etude_id')->nullable();
        });
        $this->invalidation = new AcademicMetricSnapshotInvalidationService(
            new AcademicPeriodNormalizer,
        );
    }

    public function test_lmd_bulletin_move_invalidates_original_and_current_students(): void
    {
        $this->insertSnapshot(10, 20, 'semestre1', 101);
        $this->insertSnapshot(11, 20, 'semestre2', 102);
        $bulletin = new ESBTPLMDBulletin;
        $bulletin->setRawAttributes($this->context(10, 20, 1, 101), true);
        $bulletin->forceFill($this->context(11, 20, 2, 102));

        $this->invalidation->fromLmdBulletin($bulletin);

        $this->assertSame(2, $this->dirtyCount());
    }

    public function test_enrollment_move_invalidates_every_period_in_both_contexts(): void
    {
        $this->insertSnapshot(10, 20, 'semestre1', 101);
        $this->insertSnapshot(10, 20, 'semestre2', 101);
        $this->insertSnapshot(11, 20, 'semestre1', 101);
        $enrollment = new ESBTPInscription;
        $enrollment->setRawAttributes($this->context(10, 20, 1, 101), true);
        $enrollment->forceFill($this->context(11, 20, 1, 101));

        $this->invalidation->fromEnrollment($enrollment);

        $this->assertSame(3, $this->dirtyCount());
    }

    public function test_planning_move_invalidates_classes_from_both_program_scopes(): void
    {
        DB::table('esbtp_classes')->insert([
            ['id' => 10, 'filiere_id' => 1, 'niveau_etude_id' => 2],
            ['id' => 11, 'filiere_id' => 3, 'niveau_etude_id' => 4],
        ]);
        $this->insertSnapshot(10, 20, 'semestre1', 101);
        $this->insertSnapshot(11, 20, 'semestre2', 102);
        $planning = new ESBTPPlanificationAcademique;
        $planning->setRawAttributes($this->planningContext(1, 2, 1), true);
        $planning->forceFill($this->planningContext(3, 4, 2));

        $this->invalidation->fromPlanning($planning);

        $this->assertSame(2, $this->dirtyCount());
    }

    public function test_note_context_uses_academic_year_id_when_evaluation_is_not_available(): void
    {
        $this->insertSnapshot(10, 20, 'semestre1', 101);
        $note = new ESBTPNote;
        $note->setRawAttributes([
            'id' => 88,
            'evaluation_id' => null,
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'semestre' => 1,
            'etudiant_id' => 101,
        ], true);

        $this->invalidation->fromNote($note);

        $this->assertSame(1, $this->dirtyCount());
    }

    private function context(int $classId, int $yearId, int $semester, int $studentId): array
    {
        return [
            'classe_id' => $classId,
            'annee_universitaire_id' => $yearId,
            'semestre' => $semester,
            'etudiant_id' => $studentId,
        ];
    }

    private function planningContext(int $filiereId, int $niveauId, int $semester): array
    {
        return [
            'annee_universitaire_id' => 20,
            'filiere_id' => $filiereId,
            'niveau_etude_id' => $niveauId,
            'semestre' => $semester,
        ];
    }

    private function insertSnapshot(int $classId, int $yearId, string $period, int $studentId): void
    {
        DB::table('esbtp_academic_metric_snapshots')->insert([
            'context_hash' => hash('sha256', implode(':', func_get_args())),
            'scope_type' => 'student',
            'scope_id' => $studentId,
            'academic_system' => 'LMD',
            'annee_universitaire_id' => $yearId,
            'semester' => $period,
            'classe_id' => $classId,
            'etudiant_id' => $studentId,
            'coverage_pct' => 100,
            'confidence_pct' => 100,
            'level' => 'healthy',
            'metrics' => '[]',
            'factors' => '[]',
            'reasons' => json_encode(['Snapshot de test.'], JSON_THROW_ON_ERROR),
            'evidence_hash' => hash('sha256', 'evidence'.implode(':', func_get_args())),
            'is_dirty' => false,
            'calculated_at' => now(),
        ]);
    }

    private function dirtyCount(): int
    {
        return DB::table('esbtp_academic_metric_snapshots')->where('is_dirty', true)->count();
    }
}
