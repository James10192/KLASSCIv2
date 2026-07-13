<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Contracts\AcademicCohortHealthProvider;
use App\Domain\AcademicPilotage\DTO\AcademicHealthResult;
use App\Domain\AcademicPilotage\DTO\AcademicMetricEvidenceSchema;
use App\Domain\AcademicPilotage\DTO\AcademicMetricSet;
use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;

final class AcademicMetricSnapshotCohortProvider implements AcademicCohortHealthProvider
{
    private const CHUNK_SIZE = 500;

    public function __construct(private readonly AcademicPeriodNormalizer $periods) {}

    public function healthForCohort(
        int $classId,
        int $academicYearId,
        string $academicSystem,
        string $period,
        array $studentIds,
    ): array {
        $studentIds = array_values(array_unique(array_map('intval', $studentIds)));
        $snapshots = $this->loadSnapshots(
            $classId,
            $academicYearId,
            $academicSystem,
            $this->periods->normalize($period),
            $studentIds,
        );

        $results = [];
        foreach ($studentIds as $studentId) {
            $results[$studentId] = isset($snapshots[$studentId])
                ? $this->hydrate($snapshots[$studentId])
                : $this->missingResult();
        }

        return $results;
    }

    /** @return array<int, AcademicMetricSnapshot> */
    private function loadSnapshots(
        int $classId,
        int $academicYearId,
        string $academicSystem,
        string $period,
        array $studentIds,
    ): array {
        $snapshots = [];
        foreach (array_chunk($studentIds, self::CHUNK_SIZE) as $chunk) {
            AcademicMetricSnapshot::query()
                ->where('scope_type', 'student')
                ->where('classe_id', $classId)
                ->where('annee_universitaire_id', $academicYearId)
                ->where('academic_system', $academicSystem)
                ->where('semester', $period)
                ->where('engine_version', (string) config('academic_pilotage.engine_version', '1.0.0'))
                ->where('is_dirty', false)
                ->whereIn('etudiant_id', $chunk)
                ->get()
                ->each(function (AcademicMetricSnapshot $snapshot) use (&$snapshots): void {
                    $snapshots[(int) $snapshot->etudiant_id] = $snapshot;
                });
        }

        return $snapshots;
    }

    private function hydrate(AcademicMetricSnapshot $snapshot): AcademicHealthResult
    {
        $metrics = array_map(
            fn (array $metric): AcademicMetricValue => $this->hydrateMetric($metric),
            array_values($snapshot->metrics ?? []),
        );

        return new AcademicHealthResult(
            score: $snapshot->academic_score === null ? null : (float) $snapshot->academic_score,
            coveragePct: (int) $snapshot->coverage_pct,
            confidencePct: (int) $snapshot->confidence_pct,
            level: (string) $snapshot->level,
            metrics: new AcademicMetricSet($metrics),
            factors: $snapshot->factors ?? [],
            explanations: $snapshot->reasons ?: ['Résultat académique chargé depuis le snapshot de cohorte.'],
        );
    }

    private function hydrateMetric(array $metric): AcademicMetricValue
    {
        return new AcademicMetricValue(
            key: (string) $metric['key'],
            value: isset($metric['value']) ? (float) $metric['value'] : null,
            confidencePct: (int) ($metric['confidence_pct'] ?? 0),
            evidence: AcademicMetricEvidenceSchema::redactLegacy(
                (string) $metric['key'],
                (array) ($metric['evidence'] ?? []),
            ),
            coveragePct: isset($metric['coverage_pct']) ? (int) $metric['coverage_pct'] : null,
            numerator: isset($metric['numerator']) ? (float) $metric['numerator'] : null,
            denominator: isset($metric['denominator']) ? (float) $metric['denominator'] : null,
            applicable: (bool) ($metric['applicable'] ?? true),
            reasons: (array) ($metric['reasons'] ?? []),
        );
    }

    private function missingResult(): AcademicHealthResult
    {
        return new AcademicHealthResult(
            score: null,
            coveragePct: 0,
            confidencePct: 0,
            level: 'insufficient_data',
            metrics: new AcademicMetricSet([]),
            factors: [],
            explanations: ['Aucun snapshot académique exploitable pour cet étudiant.'],
        );
    }
}
