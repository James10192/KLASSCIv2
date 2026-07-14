<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Enums\AcademicAlertStatus;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\AcademicAlert;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class AcademicPilotageSummaryService
{
    public function summarize(
        ?int $yearId,
        string $period,
        ?string $system,
        ?int $classId,
        ?Collection $classIds = null,
    ): array {
        $snapshots = $this->scopeClasses(
            AcademicMetricSnapshot::query()
                ->where('annee_universitaire_id', $yearId)
                ->where('semester', $period)
                ->when($system, fn (Builder $query) => $query->where('academic_system', $system))
                ->when($classId, fn (Builder $query) => $query->where('classe_id', $classId)),
            $classIds,
        );
        $alerts = $this->scopeClasses(
            AcademicAlert::query()
                ->where('annee_universitaire_id', $yearId)
                ->where('semester', $period)
                ->when($system, fn (Builder $query) => $this->filterAlertsBySystem($query, $system))
                ->when($classId, fn (Builder $query) => $query->where('classe_id', $classId)),
            $classIds,
        );
        $sheets = $this->scopeClasses(
            GradeSheet::query()
                ->where('annee_universitaire_id', $yearId)
                ->where('semester', $period)
                ->when($system, fn (Builder $query) => $query->where('academic_system', $system))
                ->when($classId, fn (Builder $query) => $query->where('classe_id', $classId)),
            $classIds,
        );
        $activeStatuses = AcademicAlertStatus::activeValues();
        $academicScore = (clone $snapshots)->where('scope_type', 'class')->avg('academic_score');
        $operationalScore = (clone $snapshots)->where('scope_type', 'class')->avg('operational_score');

        return [
            'academic_score' => $academicScore === null ? null : round((float) $academicScore, 2),
            'operational_score' => $operationalScore === null ? null : round((float) $operationalScore, 2),
            'open_alerts' => (clone $alerts)->whereIn('status', $activeStatuses)->count(),
            'blocking_alerts' => (clone $alerts)
                ->whereIn('status', $activeStatuses)
                ->where('severity', 'blocking')
                ->count(),
            'sheets_pending' => (clone $sheets)->whereNotIn('status', [
                GradeSheetStatus::VALIDATED->value,
                GradeSheetStatus::CANCELLED->value,
            ])->count(),
            'bulletin_blockers' => (clone $alerts)
                ->whereIn('status', $activeStatuses)
                ->where('type', 'bulletin_blocked')
                ->count(),
        ];
    }

    private function scopeClasses(Builder $query, ?Collection $classIds): Builder
    {
        return $classIds === null ? $query : $query->whereIn('classe_id', $classIds);
    }

    private function filterAlertsBySystem(Builder $query, string $system): void
    {
        $query->whereHas('classe', fn (Builder $classes) => $system === AcademicSystemNormalizer::BTS
            ? $classes->where(fn (Builder $scope) => $scope
                ->where('systeme_academique', AcademicSystemNormalizer::BTS)
                ->orWhereNull('systeme_academique')
                ->orWhere('systeme_academique', ''))
            : $classes->where('systeme_academique', AcademicSystemNormalizer::LMD));
    }
}
