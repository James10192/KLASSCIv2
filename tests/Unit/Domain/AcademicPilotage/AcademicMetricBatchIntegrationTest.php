<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotCohortProvider;
use App\Domain\AcademicPilotage\Services\AcademicMetricsProviderResolver;
use App\Domain\AcademicPilotage\Services\AcademicOperationalMetricsService;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Domain\AcademicPilotage\Services\AttendanceMetricService;
use App\Domain\AcademicPilotage\Services\ClassAcademicHealthService;
use App\Domain\AcademicPilotage\Services\GradeCompletionMetricService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class AcademicMetricBatchIntegrationTest extends AcademicPilotageDatabaseTestCase
{
    private AcademicPeriodNormalizer $periods;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periods = new AcademicPeriodNormalizer;
        $this->createSourceSchema();
    }

    public function test_resolver_rejects_a_system_that_differs_from_the_class(): void
    {
        $this->insertClass(10, 'LMD');
        $resolver = app(AcademicMetricsProviderResolver::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ne correspond pas à celui de la classe');

        $resolver->metricsFor(new StudentMetricContext(101, 10, 20, 'BTS', 'S1'));
    }

    public function test_completion_counts_missing_entries_and_hashes_entry_state(): void
    {
        $enteredSheet = $this->createGradeSheet([
            'academic_system' => 'BTS',
            'semester' => 'semestre1',
        ]);
        $this->createGradeSheet([
            'academic_system' => 'BTS',
            'semester' => 'semestre1',
        ]);
        $this->createGradeSheet([
            'academic_system' => 'BTS',
            'semester' => 'semestre1',
            'status' => 'cancelled',
        ]);
        $entryId = $this->createEntry($enteredSheet, 101);
        $service = new GradeCompletionMetricService($this->periods);
        $context = new StudentMetricContext(101, 10, 20, 'BTS', 'S1');

        $before = $service->forStudent($context);
        DB::table('esbtp_grade_sheet_entries')->where('id', $entryId)->update(['status' => 'entered']);
        $after = $service->forStudent($context);

        $this->assertSame(2.0, $before->denominator);
        $this->assertSame(0.0, $before->value);
        $this->assertSame(50.0, $after->value);
        $this->assertNotSame($before->evidence['evidence_hash'], $after->evidence['evidence_hash']);
    }

    public function test_manual_attendance_coverage_uses_expected_planning_hours(): void
    {
        $this->insertClass(10, 'BTS', 1, 2);
        DB::table('esbtp_planifications_academiques')->insert([
            'annee_universitaire_id' => 20,
            'filiere_id' => 1,
            'niveau_etude_id' => 2,
            'semestre' => 1,
            'volume_horaire_total' => 20,
            'is_active' => true,
        ]);
        DB::table('esbtp_attendance_manual_hours')->insert([
            'etudiant_id' => 101,
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'periode' => 'semestre1',
            'heures_presence' => 8,
            'heures_absence_justifiees' => 1,
            'heures_absence_non_justifiees' => 1,
        ]);

        $metric = (new AttendanceMetricService($this->periods))->forStudent(
            new StudentMetricContext(101, 10, 20, 'BTS', 'S1'),
        );

        $this->assertSame(80.0, $metric->value);
        $this->assertSame(50, $metric->effectiveCoveragePct());
        $this->assertSame(50, $metric->confidencePct);
        $this->assertSame(20.0, $metric->evidence['scheduled_hours']);
    }

    public function test_class_batch_query_count_is_constant_and_population_is_complete(): void
    {
        $this->insertClass(10, 'BTS');
        foreach (range(1, 40) as $offset) {
            $studentId = 100 + $offset;
            $this->insertActiveStudent($studentId);
            if ($offset <= 20) {
                $this->insertSnapshot($studentId);
            }
        }
        $service = $this->classService();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $result = $service->evaluate(10, 20, 'BTS', 'S1');
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(5, $queryCount);
        $this->assertCount(40, $result->studentResults);
        $this->assertSame(40, $result->studentCount);
        $this->assertSame(20, $result->scoredStudentCount);
        $this->assertSame(50, $result->coveragePct);
        $this->assertNull($result->score);
    }

    public function test_class_rejects_dispatch_mismatch_before_loading_cohort(): void
    {
        $this->insertClass(10, 'LMD');

        $this->expectException(InvalidArgumentException::class);
        $this->classService()->evaluate(10, 20, 'BTS', 'S1');
    }

    public function test_class_coverage_excludes_partial_results_without_a_score(): void
    {
        $this->insertClass(10, 'BTS');
        foreach (range(1, 40) as $offset) {
            $studentId = 100 + $offset;
            $this->insertActiveStudent($studentId);
            $offset <= 30
                ? $this->insertSnapshot($studentId)
                : $this->insertSnapshot($studentId, null, 50, 'insufficient_data');
        }

        $result = $this->classService()->evaluate(10, 20, 'BTS', 'S1');

        $this->assertSame(75, $result->coveragePct);
        $this->assertSame(30, $result->scoredStudentCount);
        $this->assertSame(80.0, $result->score);
    }

    private function classService(): ClassAcademicHealthService
    {
        return new ClassAcademicHealthService(
            new AcademicOperationalMetricsService($this->periods),
            $this->periods,
            new AcademicMetricSnapshotCohortProvider($this->periods),
        );
    }

    private function insertClass(
        int $classId,
        string $system,
        ?int $filiereId = null,
        ?int $niveauId = null,
    ): void {
        DB::table('esbtp_classes')->insert([
            'id' => $classId,
            'systeme_academique' => $system,
            'filiere_id' => $filiereId,
            'niveau_etude_id' => $niveauId,
        ]);
    }

    private function insertActiveStudent(int $studentId): void
    {
        DB::table('esbtp_inscriptions')->insert([
            'etudiant_id' => $studentId,
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);
    }

    private function insertSnapshot(
        int $studentId,
        ?float $score = 80,
        int $coverage = 100,
        string $level = 'healthy',
    ): void {
        $row = [
            'context_hash' => hash('sha256', "batch-{$studentId}"),
            'scope_type' => 'student',
            'scope_id' => $studentId,
            'academic_system' => 'BTS',
            'annee_universitaire_id' => 20,
            'semester' => 'semestre1',
            'classe_id' => 10,
            'etudiant_id' => $studentId,
            'academic_score' => $score,
            'coverage_pct' => $coverage,
            'confidence_pct' => $coverage,
            'level' => $level,
            'metrics' => '[]',
            'factors' => '[]',
            'reasons' => json_encode(['Snapshot de test.']),
            'evidence_hash' => hash('sha256', "evidence-{$studentId}"),
            'engine_version' => (string) config('academic_pilotage.engine_version', '1.0.0'),
            'is_dirty' => false,
            'calculated_at' => now(),
        ];
        if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'source_revision')) {
            $row['source_revision'] = 0;
        }

        DB::table('esbtp_academic_metric_snapshots')->insert($row);
    }

    private function createSourceSchema(): void
    {
        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('systeme_academique');
            $table->unsignedBigInteger('filiere_id')->nullable();
            $table->unsignedBigInteger('niveau_etude_id')->nullable();
        });
        Schema::create('esbtp_attendance_manual_hours', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('matiere_id')->nullable();
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('periode');
            $table->decimal('heures_presence');
            $table->decimal('heures_absence_justifiees');
            $table->decimal('heures_absence_non_justifiees');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_planifications_academiques', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->unsignedBigInteger('filiere_id');
            $table->unsignedBigInteger('niveau_etude_id');
            $table->unsignedTinyInteger('semestre');
            $table->decimal('volume_horaire_total');
            $table->boolean('is_active');
            $table->softDeletes();
        });
    }
}
