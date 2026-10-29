<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\AcademicHealthResult;
use App\Domain\AcademicPilotage\DTO\AcademicMetricSet;
use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotService;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use JsonException;

class AcademicMetricSnapshotServiceTest extends AcademicPilotageDatabaseTestCase
{
    public function test_hash_is_deterministic_and_upsert_reuses_the_snapshot(): void
    {
        $service = new AcademicMetricSnapshotService(new AcademicPeriodNormalizer);
        $context = new StudentMetricContext(101, 10, 20, 'BTS', 'S1');
        $first = $service->storeStudent($context, $this->result(80, [
            'source' => 'manual_global',
            'records_count' => 3,
        ]));
        $second = $service->storeStudent($context, $this->result(80, [
            'records_count' => 3,
            'source' => 'manual_global',
        ]));
        $third = $service->storeStudent($context, $this->result(70, [
            'source' => 'manual_global',
            'records_count' => 3,
        ]));

        $this->assertSame($first->id, $second->id);
        $this->assertSame($second->id, $third->id);
        $this->assertSame($first->context_hash, $second->context_hash);
        $this->assertSame($first->evidence_hash, $second->evidence_hash);
        $this->assertSame('70.00', $third->academic_score);
        $this->assertSame(1, AcademicMetricSnapshot::query()->count());
    }

    public function test_hash_rejects_invalid_json_values(): void
    {
        $service = new AcademicMetricSnapshotService(new AcademicPeriodNormalizer);
        $result = new AcademicHealthResult(
            score: 80,
            coveragePct: 100,
            confidencePct: 100,
            level: 'healthy',
            metrics: new AcademicMetricSet([new AcademicMetricValue('attendance', 80, 100)]),
            factors: ['attendance' => ['invalid_utf8' => "\xB1\x31"]],
            explanations: ['Résultat calculé.'],
        );

        $this->expectException(JsonException::class);

        $service->storeStudent(new StudentMetricContext(101, 10, 20, 'BTS', 'S1'), $result);
    }

    private function result(float $score, array $evidence): AcademicHealthResult
    {
        return new AcademicHealthResult(
            score: $score,
            coveragePct: 100,
            confidencePct: 100,
            level: 'healthy',
            metrics: new AcademicMetricSet([
                new AcademicMetricValue('attendance', 80, 100, $evidence),
            ]),
            factors: [],
            explanations: ['Résultat calculé.'],
        );
    }
}
