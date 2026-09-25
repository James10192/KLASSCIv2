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

        // Le lien mène aux fiches de CETTE classe et de CE semestre dans le
        // Pilotage : c'est là que les fiches se synchronisent et se valident.
        // Le message pointait vers « Notes LMD », où elles n'existent pas.
        $fiches = route('esbtp.pilotage-academique.index', [
            'class_id' => $classId,
            'system' => 'LMD',
            'period' => $period,
            'year_id' => $academicYearId,
        ]).'#sheets';

        if (! $completion?->isAvailable()) {
            $issues[] = $this->issue(
                'grade_sheet_status_unavailable',
                'Les fiches de notes de ce semestre ne sont pas encore créées ou synchronisées. Ouvrez-les dans le Pilotage académique, synchronisez-les puis validez-les avant de générer le bulletin.',
                'blocking',
                $fiches,
            );
        } elseif (($completion->denominator ?? 0) > ($completion->numerator ?? 0)) {
            $issues[] = $this->issue(
                'missing_grade_entries',
                'Des notes attendues manquent ou sont encore en brouillon ('.((int) $completion->numerator).' validées sur '.((int) $completion->denominator).'). Faites valider les notes par l’enseignant (« Valider mes notes »), validez les fiches, puis relancez le contrôle.',
                'blocking',
                $fiches,
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

    /** @return array{code: string, message: string, severity: string, action_url?: string, action_label?: string} */
    private function issue(string $code, string $message, string $severity, ?string $actionUrl = null): array
    {
        $issue = compact('code', 'message', 'severity');
        if ($actionUrl !== null) {
            $issue['action_url'] = $actionUrl;
            $issue['action_label'] = 'Ouvrir les fiches de cette classe';
        }

        return $issue;
    }
}
