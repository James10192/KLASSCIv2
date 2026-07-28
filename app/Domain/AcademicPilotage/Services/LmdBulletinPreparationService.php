<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Contracts\BulletinPreparationService;
use App\Domain\AcademicPilotage\DTO\BulletinPreparationResult;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;

final class LmdBulletinPreparationService implements BulletinPreparationService
{
    public function __construct(private readonly StudentAcademicHealthService $health) {}

    public function prepare(
        int $studentId,
        int $classId,
        int $academicYearId,
        string $period,
    ): BulletinPreparationResult {
        $result = $this->health->evaluate(new StudentMetricContext(
            studentId: $studentId,
            classId: $classId,
            academicYearId: $academicYearId,
            academicSystem: 'LMD',
            period: $period,
        ));
        $performance = $result->metrics->get('academic_performance');
        $completion = $result->metrics->get('assessment_completion');
        $issues = [];

        if (! $completion?->isAvailable()) {
            $issues[] = $this->issue(
                'grade_sheet_status_unavailable',
                'Impossible de confirmer les fiches de notes LMD pour ce semestre. Ouvrez Notes LMD, synchronisez ou créez les fiches, puis validez-les avant de générer le bulletin.',
                'blocking',
            );
        } elseif (($completion->denominator ?? 0) > ($completion->numerator ?? 0)) {
            $issues[] = $this->issue(
                'missing_grade_entries',
                'Certaines notes attendues manquent ou ne sont pas validées dans les fiches LMD. Complétez les notes de cet étudiant, validez les fiches, puis relancez le contrôle.',
                'blocking',
            );
        }

        return new BulletinPreparationResult(
            system: 'LMD',
            studentId: $studentId,
            classId: $classId,
            academicYearId: $academicYearId,
            period: $period,
            ready: $issues === [],
            coveragePct: $result->coveragePct,
            blockingIssues: $issues,
            warnings: [],
            evidence: [
                'health_level' => $result->level,
                'health_score' => $result->score,
                'coverage_pct' => $result->coveragePct,
                'confidence_pct' => $result->confidencePct,
                'performance_is_post_generation' => true,
                'performance' => $performance?->toArray(),
                'completion' => $completion?->toArray(),
            ],
        );
    }

    /** @return array{code: string, message: string, severity: string} */
    private function issue(string $code, string $message, string $severity): array
    {
        return compact('code', 'message', 'severity');
    }
}
