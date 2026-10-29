<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\AcademicHealthResult;
use App\Domain\AcademicPilotage\DTO\AcademicMetricEvidenceSchema;
use App\Domain\AcademicPilotage\DTO\AcademicMetricSet;
use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AcademicMetricDtosTest extends TestCase
{
    public function test_metric_value_accepts_aggregate_evidence_without_pii(): void
    {
        $metric = new AcademicMetricValue(
            key: 'attendance',
            value: 82.5,
            confidencePct: 90,
            evidence: ['scheduled_hours' => 120, 'observed_hours' => 99],
        );

        $this->assertTrue($metric->isAvailable());
        $this->assertSame(82.5, $metric->value);
        $this->assertSame(99, $metric->evidence['observed_hours']);
    }

    public function test_legacy_evidence_redaction_ignores_unknown_or_invalid_keys(): void
    {
        $this->assertSame([
            'records_count' => 2,
            'source' => 'manual_subject',
        ], AcademicMetricEvidenceSchema::redactLegacy('attendance', [
            'records_count' => 2,
            'source' => 'manual_subject',
            'student_name' => 'Legacy Name',
            'observed_hours' => 'invalid-number',
        ]));
    }

    /** @dataProvider invalidMetricProvider */
    public function test_metric_value_rejects_invalid_invariants(
        string $key,
        ?float $value,
        int $confidence,
        array $evidence,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new AcademicMetricValue($key, $value, $confidence, $evidence);
    }

    public function invalidMetricProvider(): array
    {
        return [
            'invalid key' => ['Attendance Rate', 80.0, 100, []],
            'score below range' => ['attendance', -0.1, 100, []],
            'score above range' => ['attendance', 100.1, 100, []],
            'confidence above range' => ['attendance', 80.0, 101, []],
            'unavailable with confidence' => ['attendance', null, 1, []],
            'phone key' => ['attendance', 80.0, 100, ['phone' => 22501020304]],
            'phone alias bypass' => ['attendance', 80.0, 100, ['telephone_mobile' => 22501020304]],
            'unknown key' => ['attendance', 80.0, 100, ['aggregate_total' => 10]],
            'free-form string' => ['attendance', 80.0, 100, ['source' => 'personne@example.test']],
            'object evidence' => ['attendance', 80.0, 100, ['source' => new \stdClass]],
        ];
    }

    /** @dataProvider providerEvidenceProvider */
    public function test_metric_value_accepts_each_provider_evidence_schema(string $key, array $evidence): void
    {
        $metric = new AcademicMetricValue($key, 80.0, 100, $evidence);

        $this->assertSame($evidence, $metric->evidence);
    }

    public function providerEvidenceProvider(): array
    {
        return [
            'BTS performance' => ['academic_performance', [
                'state' => 'semester_complete', 'notes_count' => 2, 'subjects_count' => 1,
                'coefficients_missing' => false, 'attendance_adjustment' => -0.5,
            ]],
            'LMD performance' => ['academic_performance', [
                'configured_credits' => 30, 'expected_credits' => 30,
                'observed_credits' => 15, 'subjects_count' => 8, 'published' => true,
            ]],
            'completion' => ['assessment_completion', [
                'configured_sheets' => 2, 'applicable_entries' => 2,
                'resolved_entries' => 1, 'missing_entries' => 1,
                'evidence_hash' => str_repeat('a', 64),
            ]],
            'attendance' => ['attendance', ['source' => 'manual_subject', 'records_count' => 2]],
            'progression' => ['progression', ['previous_period_available' => true, 'average_delta' => -1.5]],
            'alerts' => ['open_alerts', ['engine_ready' => false]],
        ];
    }

    public function test_metric_set_rejects_duplicates_and_indexes_metrics_by_key(): void
    {
        $metric = new AcademicMetricValue('attendance', 80.0, 100);
        $set = new AcademicMetricSet([$metric]);

        $this->assertSame($metric, $set->get('attendance'));
        $this->assertNull($set->get('progression'));

        $this->expectException(InvalidArgumentException::class);
        new AcademicMetricSet([$metric, $metric]);
    }

    public function test_context_requires_positive_identifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StudentMetricContext(0, 2, 3, 'BTS', 'semestre1');
    }

    public function test_metric_dtos_are_readonly(): void
    {
        foreach ([
            AcademicMetricValue::class,
            AcademicMetricSet::class,
            AcademicHealthResult::class,
            StudentMetricContext::class,
        ] as $class) {
            $this->assertTrue((new ReflectionClass($class))->isReadOnly(), "{$class} doit être immuable.");
        }
    }

    public function test_health_result_cannot_publish_a_score_as_insufficient_data(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AcademicHealthResult(
            score: 72.0,
            coveragePct: 100,
            confidencePct: 90,
            level: 'insufficient_data',
            metrics: new AcademicMetricSet([]),
            factors: [],
            explanations: ['Explication disponible.'],
        );
    }
}
