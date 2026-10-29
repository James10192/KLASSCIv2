<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Contracts\AcademicSystemMetricsProvider;
use App\Domain\AcademicPilotage\DTO\AcademicMetricSet;
use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDBulletin;
use App\Services\LMD\MatiereTreeBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class LmdAcademicMetricsProvider implements AcademicSystemMetricsProvider
{
    public function __construct(
        private readonly AcademicPeriodNormalizer $periods,
        private readonly GradeCompletionMetricService $completion,
        private readonly AttendanceMetricService $attendance,
        private readonly MatiereTreeBuilder $matieres,
        private readonly OpenAlertMetricService $openAlerts,
    ) {}

    public function metricsFor(StudentMetricContext $context): AcademicMetricSet
    {
        if ($context->academicSystem !== 'LMD') {
            throw new InvalidArgumentException('Le fournisseur LMD exige un contexte LMD.');
        }

        $semester = $this->periods->semesterNumber($context->period);
        if ($semester === null) {
            throw new InvalidArgumentException('Le fournisseur LMD exige un semestre.');
        }

        $canonical = new StudentMetricContext(
            $context->studentId,
            $context->classId,
            $context->academicYearId,
            'LMD',
            'semestre'.$semester,
        );
        $class = ESBTPClasse::query()
            ->without(['filiere', 'annee'])
            ->with(['parcours', 'niveau'])
            ->findOrFail($context->classId);
        $bulletin = $this->bulletin($canonical, $semester);
        $expectedCredits = (int) config('academic_pilotage.lmd.expected_credits_per_semester', 30);
        $configuredCredits = $this->configuredCredits($class, $semester);
        $subjectCount = $this->matieres->loadLmdMatieresForClasse($class, $semester)->count();

        return new AcademicMetricSet([
            $this->performanceMetric($bulletin, $expectedCredits, $configuredCredits, $subjectCount),
            $this->completion->forStudent($canonical),
            $this->attendance->forStudent($canonical),
            $this->progressionMetric($canonical, $bulletin, $semester),
            $this->openAlerts->forStudent($canonical),
        ]);
    }

    private function bulletin(StudentMetricContext $context, int $semester): ?ESBTPLMDBulletin
    {
        return ESBTPLMDBulletin::query()
            ->where('etudiant_id', $context->studentId)
            ->where('classe_id', $context->classId)
            ->where('annee_universitaire_id', $context->academicYearId)
            ->where('semestre', $semester)
            ->latest('id')
            ->first();
    }

    private function configuredCredits(ESBTPClasse $class, int $semester): int
    {
        if (! $class->parcours_id) {
            return 0;
        }

        return (int) DB::table('esbtp_lmd_parcours_ue as link')
            ->join('esbtp_unites_enseignement as ue', 'ue.id', '=', 'link.unite_enseignement_id')
            ->where('link.parcours_id', $class->parcours_id)
            ->where('link.semestre', $semester)
            ->where('ue.is_active', true)
            ->whereNull('ue.deleted_at')
            ->sum('ue.credit');
    }

    private function performanceMetric(
        ?ESBTPLMDBulletin $bulletin,
        int $expectedCredits,
        int $configuredCredits,
        int $subjectCount,
    ): AcademicMetricValue {
        if ($configuredCredits <= 0) {
            return AcademicMetricValue::unavailable(
                'academic_performance',
                'Cadre LMD non configure : aucune UE rattachee au semestre.',
                ['configured_credits' => 0, 'expected_credits' => $expectedCredits, 'subjects_count' => $subjectCount],
            );
        }

        if ($bulletin === null || $bulletin->moyenne_generale === null) {
            return AcademicMetricValue::unavailable(
                'academic_performance',
                'Aucun résultat LMD persisté pour cette période.',
                ['configured_credits' => $configuredCredits, 'expected_credits' => $expectedCredits, 'subjects_count' => $subjectCount],
            );
        }

        $observedCredits = min($expectedCredits, max(0, (int) $bulletin->credits_totaux));
        $coverage = $expectedCredits > 0
            ? (int) round(($observedCredits / $expectedCredits) * 100)
            : 0;
        $configurationConfidence = $configuredCredits === $expectedCredits ? 100 : 70;

        return new AcademicMetricValue(
            key: 'academic_performance',
            value: round(min(20, max(0, (float) $bulletin->moyenne_generale)) * 5, 2),
            confidencePct: min($coverage, $configurationConfidence),
            evidence: [
                'configured_credits' => $configuredCredits,
                'expected_credits' => $expectedCredits,
                'observed_credits' => $observedCredits,
                'subjects_count' => $subjectCount,
                'published' => (bool) $bulletin->is_published,
            ],
            coveragePct: $coverage,
            numerator: $observedCredits,
            denominator: $expectedCredits,
        );
    }

    private function progressionMetric(
        StudentMetricContext $context,
        ?ESBTPLMDBulletin $current,
        int $semester,
    ): AcademicMetricValue {
        if ($semester <= 1 || $current?->moyenne_generale === null) {
            return AcademicMetricValue::unavailable(
                'progression',
                'Aucune période LMD précédente comparable.',
                ['previous_period_available' => false],
            );
        }

        $previous = ESBTPLMDBulletin::query()
            ->where('etudiant_id', $context->studentId)
            ->where('classe_id', $context->classId)
            ->where('annee_universitaire_id', $context->academicYearId)
            ->where('semestre', $semester - 1)
            ->latest('id')
            ->first();

        if ($previous?->moyenne_generale === null) {
            return AcademicMetricValue::unavailable(
                'progression',
                'La moyenne LMD précédente est indisponible.',
                ['previous_period_available' => false],
            );
        }

        $delta = (float) $current->moyenne_generale - (float) $previous->moyenne_generale;

        return new AcademicMetricValue(
            key: 'progression',
            value: round(min(100, max(0, 50 + ($delta * 10))), 2),
            confidencePct: 100,
            evidence: ['previous_period_available' => true, 'average_delta' => round($delta, 2)],
        );
    }
}
