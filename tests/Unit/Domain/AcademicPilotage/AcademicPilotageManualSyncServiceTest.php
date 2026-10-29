<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Contracts\AcademicSystemMetricsProvider;
use App\Domain\AcademicPilotage\DTO\AcademicMetricSet;
use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Models\AcademicAlert;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use App\Domain\AcademicPilotage\Services\AcademicAlertDetectionService;
use App\Domain\AcademicPilotage\Services\AcademicAlertEngineService;
use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotCohortProvider;
use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotService;
use App\Domain\AcademicPilotage\Services\AcademicOperationalMetricsService;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Domain\AcademicPilotage\Services\AcademicPilotageManualSyncService;
use App\Domain\AcademicPilotage\Services\AcademicSystemNormalizer;
use App\Domain\AcademicPilotage\Services\ClassAcademicHealthService;
use App\Domain\AcademicPilotage\Services\OpenAlertMetricService;
use App\Domain\AcademicPilotage\Services\StudentAcademicHealthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicPilotageManualSyncServiceTest extends AcademicPilotageDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createClassSchema();
        DB::table('esbtp_classes')->insert([
            'id' => 10,
            'name' => 'BTS 1',
            'code' => 'BTS1',
            'annee_universitaire_id' => 20,
            'systeme_academique' => 'BTS',
            'is_active' => true,
        ]);
        DB::table('esbtp_inscriptions')->insert([
            'etudiant_id' => 101,
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_targeted_sync_recalculates_student_health_after_alert_reconciliation(): void
    {
        $openAlerts = new OpenAlertMetricService;
        $sync = $this->buildSync($this->providerWithMissingGrade($openAlerts), $openAlerts);

        $result = $sync->synchronize(20, 'semestre1', 'BTS', 10);

        $this->assertSame(1, $result['stats']['classes_processed']);
        $this->assertSame(1, $result['stats']['student_snapshots']);
        $this->assertSame(0, $result['stats']['failed']);
        $this->assertSame(1, AcademicAlert::query()->where('etudiant_id', 101)->count());

        $snapshot = AcademicMetricSnapshot::query()
            ->where('scope_type', 'student')
            ->where('etudiant_id', 101)
            ->sole();
        $openAlertMetric = collect($snapshot->metrics)->firstWhere('key', 'open_alerts');

        $this->assertSame(75.0, (float) $openAlertMetric['value']);
        $this->assertSame(1, $openAlertMetric['evidence']['sample_count']);
    }

    public function test_targeted_sync_rolls_back_alerts_and_provisional_scores_when_final_score_fails(): void
    {
        $openAlerts = new OpenAlertMetricService;
        $baseProvider = $this->providerWithMissingGrade($openAlerts);
        $failingProvider = new class($baseProvider) implements AcademicSystemMetricsProvider
        {
            private int $calls = 0;

            public function __construct(private readonly AcademicSystemMetricsProvider $baseProvider) {}

            public function metricsFor(StudentMetricContext $context): AcademicMetricSet
            {
                if (++$this->calls === 2) {
                    throw new \RuntimeException('Final score failed.');
                }

                return $this->baseProvider->metricsFor($context);
            }
        };

        $result = $this->buildSync($failingProvider, $openAlerts)
            ->synchronize(20, 'semestre1', 'BTS', 10);

        $this->assertSame(1, $result['stats']['failed']);
        $this->assertSame(0, $result['stats']['classes_processed']);
        $this->assertSame(0, AcademicAlert::query()->count());
        $this->assertSame(0, AcademicMetricSnapshot::query()->count());
    }

    public function test_global_sync_reconciles_alerts_before_clearing_dirty_class_snapshots(): void
    {
        $openAlerts = new OpenAlertMetricService;
        $state = (object) ['complete' => false];
        $provider = $this->providerWithCompletionState($openAlerts, $state);
        $sync = $this->buildSync($provider, $openAlerts);

        $sync->synchronize(20, 'semestre1', 'BTS', 10);
        $this->assertSame(1, AcademicAlert::query()->where('etudiant_id', 101)->where('status', 'open')->count());

        DB::table('esbtp_classes')->insert([
            'id' => 12,
            'name' => 'Ancienne classe',
            'code' => 'OLD',
            'annee_universitaire_id' => 20,
            'systeme_academique' => 'BTS',
            'is_active' => false,
        ]);
        $orphan = AcademicMetricSnapshot::query()->where('scope_type', 'student')->sole()->replicate();
        $orphan->forceFill([
            'context_hash' => hash('sha256', 'inactive-class-snapshot'),
            'scope_id' => 202,
            'classe_id' => 12,
            'etudiant_id' => 202,
            'is_dirty' => true,
        ])->save();

        $state->complete = true;
        AcademicMetricSnapshot::query()->update(['is_dirty' => true]);
        $result = $sync->synchronize(20, 'semestre1', 'BTS', null);

        $this->assertTrue($result['stats']['global_refresh']);
        $this->assertSame(1, $result['stats']['classes_processed']);
        $this->assertSame(0, $result['stats']['snapshots_remaining']);
        $this->assertTrue($orphan->fresh()->is_dirty);
        $this->assertSame(0, AcademicAlert::query()->where('etudiant_id', 101)->where('status', 'open')->count());
        $snapshot = AcademicMetricSnapshot::query()
            ->where('scope_type', 'student')
            ->where('classe_id', 10)
            ->sole();
        $metric = collect($snapshot->metrics)->firstWhere('key', 'open_alerts');
        $this->assertSame(100.0, (float) $metric['value']);
    }

    private function buildSync(
        AcademicSystemMetricsProvider $provider,
        OpenAlertMetricService $openAlerts,
    ): AcademicPilotageManualSyncService {
        $periods = new AcademicPeriodNormalizer;
        $studentHealth = new StudentAcademicHealthService($provider, [
            'academic_performance' => 30,
            'assessment_completion' => 25,
            'attendance' => 20,
            'progression' => 15,
            'open_alerts' => 10,
        ]);
        $snapshots = new AcademicMetricSnapshotService($periods);
        $classHealth = new ClassAcademicHealthService(
            new AcademicOperationalMetricsService($periods),
            $periods,
            new AcademicMetricSnapshotCohortProvider($periods),
        );

        return new AcademicPilotageManualSyncService(
            $studentHealth,
            $classHealth,
            $snapshots,
            new AcademicAlertDetectionService($classHealth, new AcademicAlertEngineService, $periods),
            $openAlerts,
            new AcademicSystemNormalizer,
            $periods,
        );
    }

    private function providerWithMissingGrade(OpenAlertMetricService $openAlerts): AcademicSystemMetricsProvider
    {
        return new class($openAlerts) implements AcademicSystemMetricsProvider
        {
            public function __construct(private readonly OpenAlertMetricService $openAlerts) {}

            public function metricsFor(StudentMetricContext $context): AcademicMetricSet
            {
                return new AcademicMetricSet([
                    new AcademicMetricValue('academic_performance', 80, 100),
                    new AcademicMetricValue(
                        'assessment_completion',
                        0,
                        100,
                        numerator: 0,
                        denominator: 1,
                    ),
                    new AcademicMetricValue('attendance', 100, 100),
                    new AcademicMetricValue('progression', 80, 100),
                    $this->openAlerts->forStudent($context),
                ]);
            }
        };
    }

    private function providerWithCompletionState(
        OpenAlertMetricService $openAlerts,
        object $state,
    ): AcademicSystemMetricsProvider {
        return new class($openAlerts, $state) implements AcademicSystemMetricsProvider
        {
            public function __construct(
                private readonly OpenAlertMetricService $openAlerts,
                private readonly object $state,
            ) {}

            public function metricsFor(StudentMetricContext $context): AcademicMetricSet
            {
                $completed = $this->state->complete ? 1.0 : 0.0;

                return new AcademicMetricSet([
                    new AcademicMetricValue('academic_performance', 80, 100),
                    new AcademicMetricValue(
                        'assessment_completion',
                        $completed * 100,
                        100,
                        numerator: $completed,
                        denominator: 1,
                    ),
                    new AcademicMetricValue('attendance', 100, 100),
                    new AcademicMetricValue('progression', 80, 100),
                    $this->openAlerts->forStudent($context),
                ]);
            }
        };
    }

    private function createClassSchema(): void
    {
        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('systeme_academique')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
