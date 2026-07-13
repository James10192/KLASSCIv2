<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\AcademicHealthResult;
use App\Domain\AcademicPilotage\DTO\ClassAcademicHealthResult;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use Illuminate\Support\Facades\Schema;

final class AcademicMetricSnapshotService
{
    private ?bool $sourceRevisionAvailable = null;

    public function __construct(private readonly AcademicPeriodNormalizer $periods) {}

    public function storeStudent(
        StudentMetricContext $context,
        AcademicHealthResult $result,
        ?int $expectedSourceRevision = null,
    ): AcademicMetricSnapshot {
        return $this->persistStudent($context, $result, $expectedSourceRevision) ?? $this->existingStudentSnapshot($context);
    }

    public function storeStudentWhenRevisionCurrent(
        StudentMetricContext $context,
        AcademicHealthResult $result,
        int $expectedSourceRevision,
    ): bool {
        return $this->persistStudent($context, $result, $expectedSourceRevision, true) !== null;
    }

    private function persistStudent(
        StudentMetricContext $context,
        AcademicHealthResult $result,
        ?int $expectedSourceRevision = null,
        bool $requireRevisionMatch = false,
    ): ?AcademicMetricSnapshot {
        $payload = $result->toArray();

        return $this->persist($this->studentContext($context), [
            'academic_score' => $result->score,
            'operational_score' => null,
            'coverage_pct' => $result->coveragePct,
            'confidence_pct' => $result->confidencePct,
            'level' => $result->level,
            'metrics' => $payload['metrics'],
            'factors' => $result->factors,
            'reasons' => $result->explanations,
            'evidence_hash' => $this->hash($payload),
        ], $expectedSourceRevision, $requireRevisionMatch);
    }

    private function existingStudentSnapshot(StudentMetricContext $context): AcademicMetricSnapshot
    {
        $contextHash = $this->hash($this->studentContext($context));

        return AcademicMetricSnapshot::query()->where('context_hash', $contextHash)->firstOrFail();
    }

    public function storeClass(
        int $academicYearId,
        string $academicSystem,
        string $period,
        ClassAcademicHealthResult $result,
        ?int $expectedSourceRevision = null,
    ): AcademicMetricSnapshot {
        return $this->persistClass($academicYearId, $academicSystem, $period, $result, $expectedSourceRevision)
            ?? $this->existingClassSnapshot($academicYearId, $academicSystem, $period, $result->classId);
    }

    public function storeClassWhenRevisionCurrent(
        int $academicYearId,
        string $academicSystem,
        string $period,
        ClassAcademicHealthResult $result,
        int $expectedSourceRevision,
    ): bool {
        return $this->persistClass($academicYearId, $academicSystem, $period, $result, $expectedSourceRevision, true) !== null;
    }

    private function persistClass(
        int $academicYearId,
        string $academicSystem,
        string $period,
        ClassAcademicHealthResult $result,
        ?int $expectedSourceRevision = null,
        bool $requireRevisionMatch = false,
    ): ?AcademicMetricSnapshot {
        $period = $this->periods->normalize($period);
        $payload = $result->toArray();
        unset($payload['student_results']);

        return $this->persist([
            'scope_type' => 'class',
            'scope_id' => $result->classId,
            'academic_system' => $academicSystem,
            'annee_universitaire_id' => $academicYearId,
            'semester' => $period,
            'classe_id' => $result->classId,
            'etudiant_id' => null,
            'user_id' => null,
        ], [
            'academic_score' => $result->score,
            'operational_score' => $result->operationalScore,
            'coverage_pct' => $result->coveragePct,
            'confidence_pct' => $result->confidencePct,
            'level' => $result->level,
            'metrics' => [
                'student_count' => $result->studentCount,
                'scored_student_count' => $result->scoredStudentCount,
                'operational' => $result->operationalMetrics,
                'operational_coverage_pct' => $result->operationalCoveragePct,
            ],
            'factors' => null,
            'reasons' => $result->reasons,
            'evidence_hash' => $this->hash($payload),
        ], $expectedSourceRevision, $requireRevisionMatch);
    }

    private function existingClassSnapshot(
        int $academicYearId,
        string $academicSystem,
        string $period,
        int $classId,
    ): AcademicMetricSnapshot {
        $contextHash = $this->hash($this->normalizeContext([
            'scope_type' => 'class',
            'scope_id' => $classId,
            'academic_system' => $academicSystem,
            'annee_universitaire_id' => $academicYearId,
            'semester' => $period,
            'classe_id' => $classId,
            'etudiant_id' => null,
            'user_id' => null,
        ]));

        return AcademicMetricSnapshot::query()->where('context_hash', $contextHash)->firstOrFail();
    }

    public function sourceRevision(array $context): int
    {
        if (! $this->sourceRevisionIsAvailable()) {
            return 0;
        }

        $contextHash = $this->hash($this->normalizeContext($context));

        return (int) AcademicMetricSnapshot::query()
            ->where('context_hash', $contextHash)
            ->value('source_revision');
    }

    private function studentContext(StudentMetricContext $context): array
    {
        return [
            'scope_type' => 'student',
            'scope_id' => $context->studentId,
            'academic_system' => $context->academicSystem,
            'annee_universitaire_id' => $context->academicYearId,
            'semester' => $this->periods->normalize($context->period),
            'classe_id' => $context->classId,
            'etudiant_id' => $context->studentId,
            'user_id' => null,
        ];
    }

    private function persist(
        array $context,
        array $metrics,
        ?int $expectedSourceRevision,
        bool $requireRevisionMatch = false,
    ): ?AcademicMetricSnapshot {
        $context = $this->normalizeContext($context);
        $contextHash = $this->hash($context);
        $currentRevision = $this->sourceRevision($context);

        $values = $this->encodeJsonColumns(array_merge($context, $metrics, [
            'context_hash' => $contextHash,
            'engine_version' => (string) config('academic_pilotage.engine_version', '1.0.0'),
            'is_dirty' => false,
            'stale_at' => null,
            'calculated_at' => now(),
        ]));
        if ($this->sourceRevisionIsAvailable()) {
            $values['source_revision'] = $expectedSourceRevision ?? $currentRevision;
        }
        if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_token')) {
            $values['refresh_token'] = null;
        }
        if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_started_at')) {
            $values['refresh_started_at'] = null;
        }

        if ($this->sourceRevisionIsAvailable() && $expectedSourceRevision !== null) {
            $affected = AcademicMetricSnapshot::query()
                ->where('context_hash', $contextHash)
                ->whereRaw('COALESCE(source_revision, 0) = ?', [$expectedSourceRevision])
                ->update(array_diff_key($values, ['context_hash' => true]));

            if ($affected > 0) {
                return AcademicMetricSnapshot::query()->where('context_hash', $contextHash)->firstOrFail();
            }

            $existing = AcademicMetricSnapshot::query()->where('context_hash', $contextHash)->first();
            if ($existing !== null) {
                return $requireRevisionMatch ? null : $existing;
            }

            if ($requireRevisionMatch) {
                return null;
            }
        }

        $updateColumns = array_values(array_diff(array_keys($values), ['context_hash']));

        AcademicMetricSnapshot::query()->upsert([$values], ['context_hash'], $updateColumns);

        return AcademicMetricSnapshot::query()->where('context_hash', $contextHash)->firstOrFail();
    }

    private function sourceRevisionIsAvailable(): bool
    {
        return $this->sourceRevisionAvailable ??= Schema::hasColumn(
            'esbtp_academic_metric_snapshots',
            'source_revision',
        );
    }

    private function normalizeContext(array $context): array
    {
        if (isset($context['academic_system'])) {
            $context['academic_system'] = strtoupper(trim((string) $context['academic_system']));
        }

        if (isset($context['semester'])) {
            $context['semester'] = $this->periods->normalize($context['semester']);
        }

        return $context;
    }

    private function encodeJsonColumns(array $values): array
    {
        foreach (['metrics', 'factors', 'reasons'] as $column) {
            if ($values[$column] !== null) {
                $values[$column] = json_encode($values[$column], JSON_THROW_ON_ERROR);
            }
        }

        return $values;
    }

    private function hash(array $data): string
    {
        return hash('sha256', json_encode(
            $this->normalize($data),
            JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        ));
    }

    private function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $normalized = array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
        if (! array_is_list($normalized)) {
            ksort($normalized, SORT_STRING);
        }

        return $normalized;
    }
}
