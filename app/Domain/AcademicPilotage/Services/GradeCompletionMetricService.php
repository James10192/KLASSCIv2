<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetEntry;
use Illuminate\Support\Collection;

final class GradeCompletionMetricService
{
    public function __construct(private readonly AcademicPeriodNormalizer $periods) {}

    public function forStudent(StudentMetricContext $context): AcademicMetricValue
    {
        return $this->forCohort([$context])[$context->studentId];
    }

    /**
     * @param  list<StudentMetricContext>  $contexts
     * @return array<int, AcademicMetricValue>
     */
    public function forCohort(array $contexts): array
    {
        if ($contexts === []) {
            return [];
        }

        $metrics = [];
        foreach (collect($contexts)->groupBy($this->contextScopeKey(...)) as $group) {
            $context = $group->first();
            $sheetIds = $this->sheetIds($context);
            if ($sheetIds->isEmpty()) {
                $metrics += $this->unavailableMetrics($group->all());
                continue;
            }

            $studentIds = $group->pluck('studentId')->all();
            $entries = GradeSheetEntry::query()
                ->whereIn('grade_sheet_id', $sheetIds)
                ->whereIn('etudiant_id', $studentIds)
                ->get(['grade_sheet_id', 'etudiant_id', 'status'])
                ->groupBy('etudiant_id');
            foreach ($studentIds as $studentId) {
                $metrics[$studentId] = $this->metric($sheetIds, $entries->get($studentId, collect()));
            }
        }

        return $metrics;
    }

    private function contextScopeKey(StudentMetricContext $context): string
    {
        return implode(':', [$context->classId, $context->academicYearId, $context->academicSystem, $context->period]);
    }

    private function sheetIds(StudentMetricContext $context): Collection
    {
        return GradeSheet::query()
            ->where('classe_id', $context->classId)
            ->where('annee_universitaire_id', $context->academicYearId)
            ->where('academic_system', $context->academicSystem)
            ->whereIn('semester', $this->periods->databaseVariants($context->period))
            ->where('status', GradeSheetStatus::VALIDATED->value)
            ->where('source', 'evaluation')
            ->orderBy('id')
            ->pluck('id');
    }

    private function unavailableMetrics(array $contexts): array
    {
        $metrics = [];
        foreach ($contexts as $context) {
            $metrics[$context->studentId] = AcademicMetricValue::unavailable(
                'assessment_completion',
                'Cadre d\'evaluation non configure pour cette periode.',
                ['configured_sheets' => 0],
            );
        }

        return $metrics;
    }

    private function metric(Collection $sheetIds, Collection $entries): AcademicMetricValue
    {
        if ($entries->isEmpty()) {
            return AcademicMetricValue::unavailable(
                'assessment_completion',
                'Aucune entree de note pour ce contexte, calcul non encore lance.',
                [
                    'configured_sheets' => $sheetIds->count(),
                    'applicable_entries' => $sheetIds->count(),
                    'resolved_entries' => 0,
                    'missing_entries' => $sheetIds->count(),
                    'evidence_hash' => hash('sha256', json_encode($sheetIds->all(), JSON_THROW_ON_ERROR)),
                ],
                true,
            );
        }

        $states = $sheetIds->mapWithKeys(fn ($sheetId): array => [(int) $sheetId => 'missing']);
        foreach ($entries as $entry) {
            $states[(int) $entry->grade_sheet_id] = $entry->status->value;
        }

        $resolvedStatuses = [
            GradeSheetEntryStatus::ENTERED->value,
            GradeSheetEntryStatus::ABSENT->value,
            GradeSheetEntryStatus::EXEMPT->value,
        ];
        $applicable = $states->reject(
            fn (string $status): bool => $status === GradeSheetEntryStatus::NOT_APPLICABLE->value,
        );
        $resolved = $states
            ->filter(fn (string $status): bool => in_array($status, $resolvedStatuses, true))
            ->count();
        $denominator = $applicable->count();

        if ($denominator === 0) {
            return AcademicMetricValue::unavailable(
                'assessment_completion',
                'Aucune fiche applicable pour cet etudiant sur cette periode.',
                [
                    'configured_sheets' => $sheetIds->count(),
                    'applicable_entries' => 0,
                    'resolved_entries' => 0,
                    'missing_entries' => 0,
                    'evidence_hash' => hash('sha256', json_encode($states->all(), JSON_THROW_ON_ERROR)),
                ],
                false,
            );
        }

        return new AcademicMetricValue(
            key: 'assessment_completion',
            value: round(($resolved / $denominator) * 100, 2),
            confidencePct: 100,
            evidence: [
                'configured_sheets' => $denominator,
                'applicable_entries' => $denominator,
                'resolved_entries' => $resolved,
                'missing_entries' => max(0, $denominator - $resolved),
                'evidence_hash' => hash('sha256', json_encode($states->all(), JSON_THROW_ON_ERROR)),
            ],
            coveragePct: 100,
            numerator: $resolved,
            denominator: $denominator,
        );
    }
}
