<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\AcademicAlertCandidate;
use App\Domain\AcademicPilotage\DTO\AcademicHealthResult;
use App\Domain\AcademicPilotage\DTO\ClassAcademicHealthResult;
use App\Domain\AcademicPilotage\Enums\AcademicAlertSeverity;
use App\Domain\AcademicPilotage\Enums\AcademicAlertType;
use App\Domain\AcademicPilotage\Models\AcademicAlert;

final class AcademicAlertDetectionService
{
    public function __construct(
        private readonly ClassAcademicHealthService $classes,
        private readonly AcademicAlertEngineService $alerts,
        private readonly AcademicPeriodNormalizer $periods,
    ) {}

    /** @return list<AcademicAlert> */
    public function refreshClass(
        int $classId,
        int $academicYearId,
        string $academicSystem,
        string $period,
    ): array {
        $period = $this->normalizePeriodForAlerts($period);
        $result = $this->classes->evaluate($classId, $academicYearId, $academicSystem, $period);
        $candidates = [
            ...$this->classCandidates($result, $academicYearId, $academicSystem, $period),
            ...$this->studentCandidates($result, $academicYearId, $academicSystem, $period),
        ];

        $alerts = array_map(
            fn (AcademicAlertCandidate $candidate): AcademicAlert => $this->alerts->upsert($candidate),
            $candidates,
        );
        $this->alerts->resolveMissingForScope(
            $classId,
            $academicYearId,
            $period,
            $this->managedTypeValues(),
            array_map(fn (AcademicAlert $alert): string => $alert->fingerprint, $alerts),
        );

        return $alerts;
    }

    /** @return list<AcademicAlertCandidate> */
    private function classCandidates(
        ClassAcademicHealthResult $result,
        int $academicYearId,
        string $academicSystem,
        string $period,
    ): array {
        $metrics = $result->operationalMetrics;
        $configuredSheets = (int) ($metrics['configured_sheets'] ?? 0);
        $unassignedReceived = (int) ($metrics['unassigned_received_sheets'] ?? 0);
        $candidates = [];

        if ($configuredSheets === 0) {
            $candidates[] = $this->candidate(
                AcademicAlertType::ASSESSMENT_NOT_CONFIGURED,
                AcademicAlertSeverity::BLOCKING,
                $result->classId,
                $academicYearId,
                $period,
                "Le cadre d'évaluation {$academicSystem} n'est pas configuré pour cette classe.",
                'Configurez les évaluations attendues avant de préparer les bulletins.',
                ['academic_system' => $academicSystem],
            );
        } elseif ($result->operationalScore !== null && $result->operationalScore < 75) {
            $candidates[] = $this->candidate(
                AcademicAlertType::CLASS_DELAYED,
                AcademicAlertSeverity::WARNING,
                $result->classId,
                $academicYearId,
                $period,
                'La classe est en retard sur la préparation des fiches et notes.',
                'Traitez les fiches attendues, reçues ou en saisie avant la génération des bulletins.',
                ['operational_score' => $result->operationalScore, 'metrics' => $metrics],
            );
        }

        if ($unassignedReceived > 0) {
            $candidates[] = $this->candidate(
                AcademicAlertType::DATA_INCONSISTENCY,
                AcademicAlertSeverity::CRITICAL,
                $result->classId,
                $academicYearId,
                $period,
                'Des fiches reçues ne sont affectées à aucun responsable de saisie.',
                'Affectez un responsable aux fiches reçues avant de poursuivre le contrôle.',
                ['unassigned_received_sheets' => $unassignedReceived],
            );
        }

        return $candidates;
    }

    /** @return list<AcademicAlertCandidate> */
    private function studentCandidates(
        ClassAcademicHealthResult $result,
        int $academicYearId,
        string $academicSystem,
        string $period,
    ): array {
        $candidates = [];

        foreach ($result->studentResults as $studentId => $studentResult) {
            $candidates = [
                ...$candidates,
                ...$this->studentMetricCandidates(
                    $studentId,
                    $result->classId,
                    $academicYearId,
                    $academicSystem,
                    $period,
                    $studentResult,
                ),
            ];
        }

        return $candidates;
    }

    /** @return list<AcademicAlertCandidate> */
    private function studentMetricCandidates(
        int $studentId,
        int $classId,
        int $academicYearId,
        string $academicSystem,
        string $period,
        AcademicHealthResult $result,
    ): array {
        $candidates = [];
        $gradeCompletion = $result->metrics->get('assessment_completion');
        $attendance = $result->metrics->get('attendance');
        $performance = $result->metrics->get('academic_performance');

        if ($gradeCompletion?->isAvailable() && ($gradeCompletion->denominator ?? 0) > ($gradeCompletion->numerator ?? 0)) {
            $candidates[] = $this->candidate(
                AcademicAlertType::MISSING_GRADE,
                AcademicAlertSeverity::BLOCKING,
                $classId,
                $academicYearId,
                $period,
                'Un étudiant a des notes attendues non résolues.',
                'Complétez la saisie, marquez une absence ou appliquez une dispense selon le cas.',
                ['student_id' => $studentId, 'academic_system' => $academicSystem, 'metric' => $gradeCompletion->toArray()],
                $studentId,
            );
        }

        if (! $performance?->isAvailable()) {
            $candidates[] = $this->candidate(
                AcademicAlertType::STUDENT_NO_AVERAGE,
                AcademicAlertSeverity::CRITICAL,
                $classId,
                $academicYearId,
                $period,
                "Un étudiant n'a pas de moyenne exploitable.",
                'Vérifiez les notes, coefficients et bulletins sources de cet étudiant.',
                ['student_id' => $studentId, 'academic_system' => $academicSystem],
                $studentId,
            );
        }

        if ($attendance?->isAvailable() && (float) $attendance->value < 70) {
            $candidates[] = $this->candidate(
                AcademicAlertType::STUDENT_HIGH_ABSENCE,
                AcademicAlertSeverity::WARNING,
                $classId,
                $academicYearId,
                $period,
                'Un étudiant présente un taux de présence insuffisant.',
                'Contrôlez les absences et lancez le suivi pédagogique approprié.',
                ['student_id' => $studentId, 'attendance_pct' => $attendance->value],
                $studentId,
            );
        }

        return $candidates;
    }

    private function candidate(
        AcademicAlertType $type,
        AcademicAlertSeverity $severity,
        int $classId,
        int $academicYearId,
        string $period,
        string $message,
        string $recommendedAction,
        array $metadata = [],
        ?int $studentId = null,
    ): AcademicAlertCandidate {
        return new AcademicAlertCandidate(
            type: $type,
            severity: $severity,
            academicYearId: $academicYearId,
            semester: $period,
            classId: $classId,
            studentId: $studentId,
            subjectId: null,
            teacherId: null,
            message: $message,
            recommendedAction: $recommendedAction,
            metadata: $metadata,
        );
    }

    private function normalizePeriodForAlerts(string $period): string
    {
        return $this->periods->normalize($period);
    }

    private function managedTypeValues(): array
    {
        return array_map(
            fn (AcademicAlertType $type): string => $type->value,
            [
                AcademicAlertType::ASSESSMENT_NOT_CONFIGURED,
                AcademicAlertType::CLASS_DELAYED,
                AcademicAlertType::DATA_INCONSISTENCY,
                AcademicAlertType::MISSING_GRADE,
                AcademicAlertType::STUDENT_NO_AVERAGE,
                AcademicAlertType::STUDENT_HIGH_ABSENCE,
            ],
        );
    }
}
