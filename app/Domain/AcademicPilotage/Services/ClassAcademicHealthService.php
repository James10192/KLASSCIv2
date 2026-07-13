<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\ClassAcademicHealthResult;
use App\Models\ESBTPInscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ClassAcademicHealthService
{
    public function __construct(
        private readonly AcademicOperationalMetricsService $operations,
        private readonly AcademicPeriodNormalizer $periods,
        private readonly AcademicMetricSnapshotCohortProvider $cohorts,
    ) {}

    public function evaluate(
        int $classId,
        int $academicYearId,
        string $academicSystem,
        string $period,
    ): ClassAcademicHealthResult {
        $period = $this->periods->normalize($period);
        $academicSystem = $this->authoritativeSystem($classId, $academicSystem);
        $studentIds = $this->cohortIds($classId, $academicYearId);
        $studentResults = $this->cohorts->healthForCohort(
            $classId,
            $academicYearId,
            $academicSystem,
            $period,
            $studentIds->map(fn ($id): int => (int) $id)->all(),
        );

        $results = collect($studentResults);
        $scored = $results->filter->hasSufficientData();
        $coverage = $results->isEmpty()
            ? 0
            : (int) round($scored->sum('coveragePct') / $results->count());
        $confidence = $results->isEmpty()
            ? 0
            : (int) round($scored->sum('confidencePct') / $results->count());
        $minimum = (int) config('academic_pilotage.student_health.minimum_coverage_pct', 60);
        $score = $coverage >= $minimum && $scored->isNotEmpty()
            ? round((float) $scored->avg('score'), 2)
            : null;
        $operations = $this->operations->forClass(
            $classId,
            $academicYearId,
            $period,
            $academicSystem,
        );

        return new ClassAcademicHealthResult(
            classId: $classId,
            score: $score,
            coveragePct: $coverage,
            confidencePct: min($coverage, $confidence),
            level: $score === null ? 'insufficient_data' : $this->level($score),
            studentCount: $studentIds->count(),
            scoredStudentCount: $scored->count(),
            operationalScore: $operations['score'],
            operationalCoveragePct: $operations['coverage_pct'],
            operationalMetrics: $operations['metrics'],
            reasons: $operations['reasons'],
            studentResults: $studentResults,
        );
    }

    private function cohortIds(int $classId, int $academicYearId): Collection
    {
        return ESBTPInscription::query()
            ->where('classe_id', $classId)
            ->where('annee_universitaire_id', $academicYearId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->pluck('etudiant_id')
            ->unique()
            ->values();
    }

    private function authoritativeSystem(int $classId, string $requested): string
    {
        $system = strtoupper(trim((string) DB::table('esbtp_classes')
            ->where('id', $classId)
            ->value('systeme_academique')));

        if (! in_array($system, ['BTS', 'LMD'], true)) {
            throw new InvalidArgumentException(
                "Le système académique de la classe n'est pas pris en charge.",
            );
        }

        if (strtoupper(trim($requested)) !== $system) {
            throw new InvalidArgumentException(
                'Le système académique demandé ne correspond pas à celui de la classe.',
            );
        }

        return $system;
    }

    private function level(float $score): string
    {
        $levels = config('academic_pilotage.student_health.levels', []);

        return match (true) {
            $score >= ($levels['healthy'] ?? 80) => 'healthy',
            $score >= ($levels['watch'] ?? 65) => 'watch',
            $score >= ($levels['at_risk'] ?? 50) => 'at_risk',
            default => 'critical',
        };
    }
}
