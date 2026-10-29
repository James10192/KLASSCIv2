<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Contracts\AcademicSystemMetricsProvider;
use App\Domain\AcademicPilotage\DTO\AcademicMetricSet;
use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use InvalidArgumentException;

final class BtsAcademicMetricsProvider implements AcademicSystemMetricsProvider
{
    public function __construct(
        private readonly BtsCurrentResultSnapshotService $snapshots,
        private readonly BtsAnnualClassMapResolver $classMap,
        private readonly AcademicPeriodNormalizer $periods,
        private readonly GradeCompletionMetricService $completion,
        private readonly AttendanceMetricService $attendance,
        private readonly OpenAlertMetricService $openAlerts,
    ) {}

    public function metricsFor(StudentMetricContext $context): AcademicMetricSet
    {
        if ($context->academicSystem !== 'BTS') {
            throw new InvalidArgumentException('Le fournisseur BTS exige un contexte BTS.');
        }

        $period = $this->periods->normalize($context->period);
        if (! in_array($period, ['semestre1', 'semestre2', 'annuel'], true)) {
            throw new InvalidArgumentException('La période BTS doit être semestre1, semestre2 ou annuel.');
        }

        $resolved = $this->resolvedContext($context, $period);
        $snapshot = $this->snapshots->getPeriodeSnapshot(
            $context->studentId,
            $resolved->classId,
            $context->academicYearId,
            $period,
        );

        return new AcademicMetricSet([
            $this->performanceMetric($snapshot),
            $this->completion->forStudent($resolved),
            $this->attendance->forStudent($resolved),
            $this->progressionMetric($resolved, $snapshot),
            $this->openAlerts->forStudent($resolved),
        ]);
    }

    private function resolvedContext(StudentMetricContext $context, string $period): StudentMetricContext
    {
        if ($period === 'annuel') {
            return new StudentMetricContext(
                $context->studentId,
                $context->classId,
                $context->academicYearId,
                'BTS',
                $period,
            );
        }

        $map = $this->classMap->resolve(
            $context->studentId,
            $context->classId,
            $context->academicYearId,
        );
        $classId = $period === 'semestre1'
            ? $map['semestre1_classe_id']
            : $map['semestre2_classe_id'];

        return new StudentMetricContext(
            $context->studentId,
            (int) $classId,
            $context->academicYearId,
            'BTS',
            $period,
        );
    }

    private function performanceMetric(array $snapshot): AcademicMetricValue
    {
        $raw = $snapshot['raw_total'] ?? null;
        $state = (string) ($snapshot['state'] ?? 'no_data');

        if ($raw === null || $state === 'annual_incomplete') {
            return AcademicMetricValue::unavailable(
                'academic_performance',
                $state === 'annual_incomplete'
                    ? 'La moyenne annuelle est incomplete.'
                : 'Aucune moyenne académique BTS disponible.',
                ['state' => $state, 'notes_count' => (int) ($snapshot['notes_count'] ?? 0)],
            );
        }

        $missingCoefficients = (bool) ($snapshot['coefficients_missing'] ?? false);

        return new AcademicMetricValue(
            key: 'academic_performance',
            value: round(min(20, max(0, (float) $raw)) * 5, 2),
            confidencePct: $missingCoefficients ? 60 : 100,
            evidence: [
                'state' => $state,
                'notes_count' => (int) ($snapshot['notes_count'] ?? 0),
                'subjects_count' => count($snapshot['subjects'] ?? []),
                'coefficients_missing' => $missingCoefficients,
                'attendance_adjustment' => (float) ($snapshot['attendance_note'] ?? 0),
            ],
            coveragePct: 100,
        );
    }

    private function progressionMetric(StudentMetricContext $context, array $current): AcademicMetricValue
    {
        $previous = $this->periods->previous($context->period);
        if ($previous === null || ($current['raw_total'] ?? null) === null) {
            return AcademicMetricValue::unavailable(
                'progression',
                'Aucune période académique précédente comparable.',
                ['previous_period_available' => false],
            );
        }

        $map = $this->classMap->resolve(
            $context->studentId,
            $context->classId,
            $context->academicYearId,
        );
        $previousClassId = $previous === 'semestre1'
            ? $map['semestre1_classe_id']
            : $map['semestre2_classe_id'];
        $previousSnapshot = $this->snapshots->getSemesterSnapshot(
            $context->studentId,
            (int) $previousClassId,
            $context->academicYearId,
            $previous,
        );
        $previousRaw = $previousSnapshot['raw_total'] ?? null;

        if ($previousRaw === null) {
            return AcademicMetricValue::unavailable(
                'progression',
                'La moyenne de la période précédente est indisponible.',
                ['previous_period_available' => false],
            );
        }

        $delta = (float) $current['raw_total'] - (float) $previousRaw;

        return new AcademicMetricValue(
            key: 'progression',
            value: round(min(100, max(0, 50 + ($delta * 10))), 2),
            confidencePct: 100,
            evidence: [
                'previous_period_available' => true,
                'average_delta' => round($delta, 2),
            ],
        );
    }
}
