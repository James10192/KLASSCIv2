<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Contracts\AcademicSystemMetricsProvider;
use App\Domain\AcademicPilotage\DTO\AcademicMetricSet;
use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotCohortProvider;
use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotRefreshService;
use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotService;
use App\Domain\AcademicPilotage\Services\AcademicOperationalMetricsService;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Domain\AcademicPilotage\Services\AttendanceMetricService;
use App\Domain\AcademicPilotage\Services\BtsAcademicMetricsProvider;
use App\Domain\AcademicPilotage\Services\ClassAcademicHealthService;
use App\Domain\AcademicPilotage\Services\GradeCompletionMetricService;
use App\Domain\AcademicPilotage\Services\LmdAcademicMetricsProvider;
use App\Domain\AcademicPilotage\Services\OpenAlertMetricService;
use App\Domain\AcademicPilotage\Services\StudentAcademicHealthService;
use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use App\Services\ESBTP\ManualAttendanceHoursService;
use App\Services\LMD\MatiereTreeBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mockery;

class AcademicMetricsIntegrationTest extends AcademicPilotageDatabaseTestCase
{
    private AcademicPeriodNormalizer $periods;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periods = new AcademicPeriodNormalizer;
        $this->createMetricSourceSchema();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_bts_uses_raw_average_without_counting_attendance_twice(): void
    {
        $this->createResolvedSheet('BTS', 'semestre2', 101);
        $this->createManualAttendance(101, 'semestre2');
        $snapshots = Mockery::mock(BtsCurrentResultSnapshotService::class);
        $snapshots->shouldReceive('getPeriodeSnapshot')->once()->andReturn($this->btsSnapshot(10, 2));
        $snapshots->shouldReceive('getSemesterSnapshot')->once()->andReturn($this->btsSnapshot(8, 1));
        $classMap = Mockery::mock(BtsAnnualClassMapResolver::class);
        $classMap->shouldReceive('resolve')->twice()->andReturn([
            'semestre1_classe_id' => 10,
            'semestre2_classe_id' => 10,
        ]);
        $provider = new BtsAcademicMetricsProvider(
            $snapshots,
            $classMap,
            $this->periods,
            new GradeCompletionMetricService($this->periods),
            new AttendanceMetricService($this->periods),
            new OpenAlertMetricService,
        );

        $metrics = $provider->metricsFor(new StudentMetricContext(101, 10, 20, 'BTS', 'S2'));

        $this->assertSame(50.0, $metrics->get('academic_performance')->value);
        $this->assertSame(2.0, $metrics->get('academic_performance')->evidence['attendance_adjustment']);
        $this->assertSame(70.0, $metrics->get('progression')->value);
        $this->assertSame(100.0, $metrics->get('assessment_completion')->value);
        $this->assertSame(100.0, $metrics->get('open_alerts')->value);
    }

    public function test_lmd_reads_persisted_results_and_uses_thirty_expected_credits(): void
    {
        $this->createLmdContext();
        $this->createResolvedSheet('LMD', 'semestre3', 101);
        $this->createManualAttendance(101, 'semestre3');
        $matieres = Mockery::mock(MatiereTreeBuilder::class);
        $matieres->shouldReceive('loadLmdMatieresForClasse')->once()->andReturn(collect([['matiere' => 'ecue']]));
        $provider = new LmdAcademicMetricsProvider(
            $this->periods,
            new GradeCompletionMetricService($this->periods),
            new AttendanceMetricService($this->periods),
            $matieres,
            new OpenAlertMetricService,
        );
        $before = DB::table('esbtp_lmd_bulletins')->count();

        $metrics = $provider->metricsFor(new StudentMetricContext(101, 10, 20, 'LMD', 'S3'));

        $performance = $metrics->get('academic_performance');
        $this->assertSame(60.0, $performance->value);
        $this->assertSame(50, $performance->effectiveCoveragePct());
        $this->assertSame(30.0, $performance->denominator);
        $this->assertSame($before, DB::table('esbtp_lmd_bulletins')->count());
        $this->assertSame(100.0, $metrics->get('open_alerts')->value);
    }

    public function test_class_level_stays_insufficient_when_full_cohort_coverage_is_low(): void
    {
        DB::table('esbtp_classes')->insert([
            'id' => 10,
            'name' => 'BTS 1',
            'systeme_academique' => 'BTS',
        ]);
        $this->insertActiveStudent(101);
        $this->insertActiveStudent(102);
        $this->createResolvedSheet('BTS', 'semestre1', 101);
        $this->insertStudentSnapshot(101, 80, 100);
        $service = new ClassAcademicHealthService(
            new AcademicOperationalMetricsService($this->periods),
            $this->periods,
            new AcademicMetricSnapshotCohortProvider($this->periods),
        );

        $result = $service->evaluate(10, 20, 'BTS', 'semestre1');

        $this->assertSame(50, $result->coveragePct);
        $this->assertNull($result->score);
        $this->assertSame('insufficient_data', $result->level);
        $this->assertSame(1, $result->scoredStudentCount);
        $this->assertNotNull($result->operationalScore);
    }

    private function insertStudentSnapshot(int $studentId, float $score, int $coverage): void
    {
        $row = [
            'context_hash' => hash('sha256', "student-{$studentId}"),
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
            'level' => 'healthy',
            'metrics' => '[]',
            'factors' => '[]',
            'reasons' => json_encode(['Snapshot de test.']),
            'evidence_hash' => hash('sha256', "evidence-{$studentId}"),
            'engine_version' => (string) config('academic_pilotage.engine_version', '1.0.0'),
            'is_dirty' => false,
            'calculated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'source_revision')) {
            $row['source_revision'] = 0;
        }

        DB::table('esbtp_academic_metric_snapshots')->insert($row);
    }

    public function test_snapshot_store_is_reproducible_and_invalidation_marks_class_and_student_dirty(): void
    {
        $provider = new class implements AcademicSystemMetricsProvider
        {
            public function metricsFor(StudentMetricContext $context): AcademicMetricSet
            {
                return new AcademicMetricSet([
                    new AcademicMetricValue('academic_performance', 75, 100),
                    new AcademicMetricValue('assessment_completion', 75, 100),
                    new AcademicMetricValue('attendance', 75, 100),
                    new AcademicMetricValue('progression', 75, 100),
                    new AcademicMetricValue('open_alerts', 75, 100),
                ]);
            }
        };
        $context = new StudentMetricContext(101, 10, 20, 'BTS', 'S1');
        $result = (new StudentAcademicHealthService($provider))->evaluate($context);
        $store = new AcademicMetricSnapshotService($this->periods);
        $first = $store->storeStudent($context, $result);
        $second = $store->storeStudent($context, $result);

        $this->assertSame($first->id, $second->id);
        $updated = (new AcademicMetricSnapshotInvalidationService($this->periods))
            ->invalidate(10, 20, 'semestre1', 101);

        $this->assertSame(1, $updated);
        $this->assertTrue($second->fresh()->is_dirty);
        $this->assertNotNull($second->fresh()->stale_at);
    }

    public function test_stale_snapshot_refresh_cannot_clear_newer_invalidation(): void
    {
        $context = new StudentMetricContext(101, 10, 20, 'BTS', 'S1');
        $store = new AcademicMetricSnapshotService($this->periods);
        $result = (new StudentAcademicHealthService($this->flatHealthProvider(75)))->evaluate($context);
        $snapshot = $store->storeStudent($context, $result);
        $revision = (int) $snapshot->source_revision;

        (new AcademicMetricSnapshotInvalidationService($this->periods))
            ->invalidate(10, 20, 'semestre1', 101);
        $store->storeStudent($context, $result, $revision);

        $fresh = $snapshot->fresh();
        $this->assertTrue($fresh->is_dirty);
        $this->assertSame($revision + 1, (int) $fresh->source_revision);
    }

    public function test_refresh_dirty_rebuilds_claimed_snapshot(): void
    {
        DB::table('esbtp_classes')->insert([
            'id' => 10,
            'name' => 'BTS 1',
            'systeme_academique' => 'BTS',
        ]);
        $context = new StudentMetricContext(101, 10, 20, 'BTS', 'S1');
        $store = new AcademicMetricSnapshotService($this->periods);
        $result = (new StudentAcademicHealthService($this->flatHealthProvider(75)))->evaluate($context);
        $snapshot = $store->storeStudent($context, $result);

        (new AcademicMetricSnapshotInvalidationService($this->periods))
            ->invalidate(10, 20, 'semestre1', 101);

        $refresh = new AcademicMetricSnapshotRefreshService(
            new StudentAcademicHealthService($this->flatHealthProvider(80)),
            new ClassAcademicHealthService(
                new AcademicOperationalMetricsService($this->periods),
                $this->periods,
                new AcademicMetricSnapshotCohortProvider($this->periods),
            ),
            $store,
        );

        $this->assertSame([
            'scanned' => 1,
            'refreshed' => 1,
            'stale_retries' => 0,
            'failed' => 0,
        ], $refresh->refreshDirty(10));
        $fresh = $snapshot->fresh();
        $this->assertFalse($fresh->is_dirty);
        $this->assertNull($fresh->refresh_token);
        $this->assertNull($fresh->refresh_started_at);
        $this->assertSame(1, (int) $fresh->refresh_attempts);
        $this->assertSame('80.00', (string) $fresh->academic_score);
    }

    public function test_scoped_refresh_only_claims_snapshots_from_the_requested_view(): void
    {
        DB::table('esbtp_classes')->insert([
            ['id' => 10, 'name' => 'BTS 1', 'systeme_academique' => 'BTS'],
            ['id' => 11, 'name' => 'BTS 2', 'systeme_academique' => 'BTS'],
        ]);
        $store = new AcademicMetricSnapshotService($this->periods);
        $health = new StudentAcademicHealthService($this->flatHealthProvider(75));
        $selected = $store->storeStudent(
            new StudentMetricContext(101, 10, 20, 'BTS', 'S1'),
            $health->evaluate(new StudentMetricContext(101, 10, 20, 'BTS', 'S1')),
        );
        $outside = $store->storeStudent(
            new StudentMetricContext(102, 11, 21, 'BTS', 'S2'),
            $health->evaluate(new StudentMetricContext(102, 11, 21, 'BTS', 'S2')),
        );
        (new AcademicMetricSnapshotInvalidationService($this->periods))->invalidate(10, 20, 'S1', 101);
        (new AcademicMetricSnapshotInvalidationService($this->periods))->invalidate(11, 21, 'S2', 102);

        $refresh = new AcademicMetricSnapshotRefreshService(
            new StudentAcademicHealthService($this->flatHealthProvider(80)),
            new ClassAcademicHealthService(
                new AcademicOperationalMetricsService($this->periods),
                $this->periods,
                new AcademicMetricSnapshotCohortProvider($this->periods),
            ),
            $store,
        );
        $result = $refresh->refreshDirtyForScope(20, 'semestre1', 'BTS', 10);

        $this->assertSame(1, $result['scanned']);
        $this->assertSame(1, $result['refreshed']);
        $this->assertSame(0, $result['remaining']);
        $this->assertFalse($result['has_more']);
        $this->assertFalse($selected->fresh()->is_dirty);
        $this->assertTrue($outside->fresh()->is_dirty);
    }

    public function test_refresh_dirty_releases_claim_when_revision_changes_during_rebuild(): void
    {
        DB::table('esbtp_classes')->insert([
            'id' => 10,
            'name' => 'BTS 1',
            'systeme_academique' => 'BTS',
        ]);
        $context = new StudentMetricContext(101, 10, 20, 'BTS', 'S1');
        $store = new AcademicMetricSnapshotService($this->periods);
        $snapshot = $store->storeStudent(
            $context,
            (new StudentAcademicHealthService($this->flatHealthProvider(75)))->evaluate($context),
        );

        (new AcademicMetricSnapshotInvalidationService($this->periods))
            ->invalidate(10, 20, 'semestre1', 101);

        $refresh = new AcademicMetricSnapshotRefreshService(
            new StudentAcademicHealthService($this->invalidatingHealthProvider(80, 10, 20, 'semestre1', 101)),
            new ClassAcademicHealthService(
                new AcademicOperationalMetricsService($this->periods),
                $this->periods,
                new AcademicMetricSnapshotCohortProvider($this->periods),
            ),
            $store,
        );

        $this->assertSame([
            'scanned' => 1,
            'refreshed' => 0,
            'stale_retries' => 1,
            'failed' => 0,
        ], $refresh->refreshDirty(10));

        $fresh = $snapshot->fresh();
        $this->assertTrue($fresh->is_dirty);
        $this->assertNull($fresh->refresh_token);
        $this->assertNull($fresh->refresh_started_at);
        $this->assertSame(1, (int) $fresh->refresh_attempts);
        $this->assertSame('source_revision_changed', $fresh->last_refresh_error);
        $this->assertSame('75.00', (string) $fresh->academic_score);
    }

    public function test_evaluation_move_invalidates_original_and_current_contexts(): void
    {
        $this->insertSnapshot('old-context', 10, 20, 'semestre1');
        $this->insertSnapshot('new-context', 11, 21, 'semestre2');
        $evaluation = new ESBTPEvaluation;
        $evaluation->setRawAttributes([
            'id' => 77,
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'periode' => 'semestre1',
        ], true);
        $evaluation->forceFill([
            'classe_id' => 11,
            'annee_universitaire_id' => 21,
            'periode' => 'semestre2',
        ]);

        (new AcademicMetricSnapshotInvalidationService($this->periods))
            ->fromEvaluation($evaluation);

        $dirty = DB::table('esbtp_academic_metric_snapshots')
            ->orderBy('context_hash')
            ->pluck('is_dirty', 'context_hash');
        $this->assertSame(1, $dirty['new-context']);
        $this->assertSame(1, $dirty['old-context']);
    }

    public function test_manual_hours_batch_invalidates_once_without_blocking_writes(): void
    {
        Schema::drop('esbtp_academic_metric_snapshots');
        Log::shouldReceive('channel')->with('queries')->zeroOrMoreTimes()->andReturnSelf();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->never();
        $service = new ManualAttendanceHoursService(
            new AcademicMetricSnapshotInvalidationService($this->periods),
        );

        $count = $service->upsertBatch([
            ['etudiant_id' => 101, 'heures_presence' => 2],
            ['etudiant_id' => 102, 'heures_presence' => 3],
        ], [
            'classe_id' => 10,
            'matiere_id' => null,
            'annee_universitaire_id' => 20,
            'periode' => 'semestre1',
        ], 42);

        $this->assertSame(2, $count);
        $this->assertSame(2, DB::table('esbtp_attendance_manual_hours')->count());
    }

    public function test_manual_hours_batch_invalidates_class_and_student_snapshots(): void
    {
        $this->insertSnapshot('manual-class-context', 10, 20, 'semestre1');
        $this->insertSnapshot('manual-student-context', 10, 20, 'semestre1', 'student', 101);
        $service = new ManualAttendanceHoursService(
            new AcademicMetricSnapshotInvalidationService($this->periods),
        );

        $count = $service->upsertBatch([
            ['etudiant_id' => 101, 'heures_presence' => 2],
        ], [
            'classe_id' => 10,
            'matiere_id' => null,
            'annee_universitaire_id' => 20,
            'periode' => 'semestre1',
        ], 42);

        $this->assertSame(1, $count);
        $this->assertTrue((bool) DB::table('esbtp_academic_metric_snapshots')->where('context_hash', 'manual-class-context')->value('is_dirty'));
        $this->assertTrue((bool) DB::table('esbtp_academic_metric_snapshots')->where('context_hash', 'manual-student-context')->value('is_dirty'));
    }

    public function test_grade_sheet_invalidates_class_and_student_snapshots(): void
    {
        $this->insertSnapshot('sheet-class-context', 10, 20, 'semestre1');
        $this->insertSnapshot('sheet-student-context', 10, 20, 'semestre1', 'student', 101);
        $sheet = $this->createGradeSheet([
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'semester' => 'semestre1',
        ]);
        $this->createEntry($sheet, 101);

        (new AcademicMetricSnapshotInvalidationService($this->periods))->fromGradeSheet($sheet);

        $this->assertTrue((bool) DB::table('esbtp_academic_metric_snapshots')->where('context_hash', 'sheet-class-context')->value('is_dirty'));
        $this->assertTrue((bool) DB::table('esbtp_academic_metric_snapshots')->where('context_hash', 'sheet-student-context')->value('is_dirty'));
    }

    public function test_context_resolution_failure_never_escapes_invalidation_boundary(): void
    {
        Schema::dropIfExists('esbtp_evaluations');
        Log::shouldReceive('channel')->with('queries')->zeroOrMoreTimes()->andReturnSelf();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->once()->with(
            'Academic metric snapshot invalidation failed.',
            Mockery::on(fn (array $context): bool => $context['source'] === 'note')
        );
        $note = new ESBTPNote;
        $note->setRawAttributes([
            'id' => 88,
            'evaluation_id' => 77,
            'etudiant_id' => 101,
        ], true);

        (new AcademicMetricSnapshotInvalidationService($this->periods))->fromNote($note);

        $this->assertTrue(true);
    }

    private function insertSnapshot(
        string $hash,
        int $classId,
        int $academicYearId,
        string $semester,
        string $scopeType = 'class',
        ?int $studentId = null,
    ): void {
        DB::table('esbtp_academic_metric_snapshots')->insert([
            'context_hash' => $hash,
            'scope_type' => $scopeType,
            'scope_id' => $studentId ?? $classId,
            'classe_id' => $classId,
            'etudiant_id' => $studentId,
            'annee_universitaire_id' => $academicYearId,
            'semester' => $semester,
            'coverage_pct' => 100,
            'confidence_pct' => 100,
            'level' => 'healthy',
            'metrics' => '{}',
            'evidence_hash' => $hash,
            'is_dirty' => false,
            'calculated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function btsSnapshot(float $raw, float $attendance): array
    {
        return [
            'state' => 'semester_complete',
            'raw_total' => $raw,
            'attendance_note' => $attendance,
            'effective_total' => $raw + $attendance,
            'subjects' => [['matiere_id' => 1]],
            'notes_count' => 1,
            'coefficients_missing' => false,
        ];
    }

    private function createResolvedSheet(string $system, string $semester, int $studentId): void
    {
        $sheet = $this->createGradeSheet([
            'academic_system' => $system,
            'semester' => $semester,
            'status' => 'entered',
        ]);
        $this->createEntry($sheet, $studentId, ['status' => 'entered']);
    }

    private function createManualAttendance(int $studentId, string $period): void
    {
        DB::table('esbtp_attendance_manual_hours')->insert([
            'etudiant_id' => $studentId,
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'periode' => $period,
            'heures_presence' => 8,
            'heures_absence_justifiees' => 1,
            'heures_absence_non_justifiees' => 1,
            'created_at' => now(),
            'updated_at' => now(),
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createLmdContext(): void
    {
        DB::table('esbtp_niveau_etudes')->insert(['id' => 30, 'type' => 'Licence', 'year' => 2]);
        DB::table('esbtp_lmd_parcours')->insert(['id' => 5, 'name' => 'Parcours', 'code' => 'P']);
        DB::table('esbtp_classes')->insert([
            'id' => 10,
            'name' => 'L2',
            'systeme_academique' => 'LMD',
            'parcours_id' => 5,
            'niveau_etude_id' => 30,
        ]);
        DB::table('esbtp_unites_enseignement')->insert([
            'id' => 40,
            'name' => 'UE',
            'code' => 'UE1',
            'credit' => 30,
            'semestre' => 3,
            'is_active' => true,
        ]);
        DB::table('esbtp_lmd_parcours_ue')->insert([
            'parcours_id' => 5,
            'unite_enseignement_id' => 40,
            'semestre' => 3,
        ]);
        DB::table('esbtp_lmd_bulletins')->insert([
            'etudiant_id' => 101,
            'classe_id' => 10,
            'parcours_id' => 5,
            'annee_universitaire_id' => 20,
            'semestre' => 3,
            'moyenne_generale' => 12,
            'credits_capitalises' => 10,
            'credits_totaux' => 15,
            'is_published' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function flatHealthProvider(int $score): AcademicSystemMetricsProvider
    {
        return new class($score) implements AcademicSystemMetricsProvider
        {
            public function __construct(private readonly int $score) {}

            public function metricsFor(StudentMetricContext $context): AcademicMetricSet
            {
                return new AcademicMetricSet([
                    new AcademicMetricValue('academic_performance', $this->score, 100),
                    new AcademicMetricValue('assessment_completion', $this->score, 100),
                    new AcademicMetricValue('attendance', $this->score, 100),
                    new AcademicMetricValue('progression', $this->score, 100),
                    new AcademicMetricValue('open_alerts', $this->score, 100),
                ]);
            }
        };
    }

    private function invalidatingHealthProvider(
        int $score,
        int $classId,
        int $academicYearId,
        string $period,
        int $studentId,
    ): AcademicSystemMetricsProvider {
        return new class($score, $classId, $academicYearId, $period, $studentId, $this->periods) implements AcademicSystemMetricsProvider
        {
            public function __construct(
                private readonly int $score,
                private readonly int $classId,
                private readonly int $academicYearId,
                private readonly string $period,
                private readonly int $studentId,
                private readonly AcademicPeriodNormalizer $periods,
            ) {}

            public function metricsFor(StudentMetricContext $context): AcademicMetricSet
            {
                (new AcademicMetricSnapshotInvalidationService($this->periods))
                    ->invalidate($this->classId, $this->academicYearId, $this->period, $this->studentId);

                return new AcademicMetricSet([
                    new AcademicMetricValue('academic_performance', $this->score, 100),
                    new AcademicMetricValue('assessment_completion', $this->score, 100),
                    new AcademicMetricValue('attendance', $this->score, 100),
                    new AcademicMetricValue('progression', $this->score, 100),
                    new AcademicMetricValue('open_alerts', $this->score, 100),
                ]);
            }
        };
    }

    private function createMetricSourceSchema(): void
    {
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
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_niveau_etudes', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->unsignedTinyInteger('year');
            $table->softDeletes();
        });
        Schema::create('esbtp_lmd_parcours', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('systeme_academique');
            $table->unsignedBigInteger('parcours_id')->nullable();
            $table->unsignedBigInteger('niveau_etude_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('esbtp_unites_enseignement', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->unsignedTinyInteger('credit');
            $table->unsignedTinyInteger('semestre');
            $table->boolean('is_active');
            $table->softDeletes();
        });
        Schema::create('esbtp_lmd_parcours_ue', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parcours_id');
            $table->unsignedBigInteger('unite_enseignement_id');
            $table->unsignedTinyInteger('semestre');
        });
        Schema::create('esbtp_lmd_bulletins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('parcours_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->unsignedTinyInteger('semestre');
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->unsignedTinyInteger('credits_capitalises')->default(0);
            $table->unsignedTinyInteger('credits_totaux')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
