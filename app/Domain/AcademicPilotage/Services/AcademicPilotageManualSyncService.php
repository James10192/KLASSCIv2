<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AcademicPilotageManualSyncService
{
    public function __construct(
        private readonly StudentAcademicHealthService $studentHealth,
        private readonly ClassAcademicHealthService $classHealth,
        private readonly AcademicMetricSnapshotService $snapshots,
        private readonly AcademicAlertDetectionService $alerts,
        private readonly OpenAlertMetricService $openAlerts,
        private readonly AcademicSystemNormalizer $systems,
        private readonly AcademicPeriodNormalizer $periods,
    ) {}

    public function synchronize(int $yearId, string $period, ?string $system, ?int $classId): array
    {
        if ($classId === null) {
            return $this->synchronizeDirtyClasses($yearId, $period, $system);
        }

        $classes = $this->classesQuery($yearId, $system, $classId)
            ->limit(1)
            ->get();

        return $this->synchronizeClasses($classes, $yearId, $period);
    }

    private function synchronizeClasses(Collection $classes, int $yearId, string $period): array
    {
        $stats = $this->emptyStats();
        $failures = [];

        foreach ($classes as $classe) {
            try {
                $classStats = DB::transaction(function () use ($classe, $yearId, $period): array {
                    $classStats = $this->emptyStats();
                    $this->synchronizeClass($classe, $yearId, $period, $classStats);

                    return $classStats;
                });
                $this->mergeStats($stats, $classStats);
            } catch (Throwable $exception) {
                $this->openAlerts->flush();
                $stats['failed']++;
                $failures[] = [
                    'class_id' => (int) $classe->id,
                    'class' => $this->classLabel($classe),
                    'message' => $exception->getMessage(),
                ];
                Log::warning('Academic pilotage manual synchronization failed for one class.', [
                    'class_id' => (int) $classe->id,
                    'academic_year_id' => $yearId,
                    'period' => $period,
                    'exception' => $exception,
                ]);
            }
        }

        return [
            'stats' => $stats,
            'failures' => $failures,
        ];
    }

    private function synchronizeDirtyClasses(int $yearId, string $period, ?string $system): array
    {
        $normalizedPeriod = $this->periods->normalize($period);
        $limit = max(1, (int) config('academic_pilotage.refresh.max_classes_per_manual_sync', 10));
        $dirtyQuery = $this->dirtySnapshotQuery($yearId, $normalizedPeriod, $system);
        $classIds = (clone $dirtyQuery)
            ->select('classe_id')
            ->distinct()
            ->orderBy('classe_id')
            ->limit($limit)
            ->pluck('classe_id');
        $scanned = (clone $dirtyQuery)->whereIn('classe_id', $classIds)->count();
        $result = $this->synchronizeClasses(
            $this->classesQuery($yearId, $system, null)->whereIn('id', $classIds)->get(),
            $yearId,
            $normalizedPeriod,
        );
        $remaining = $this->dirtySnapshotQuery($yearId, $normalizedPeriod, $system)
            ->distinct()
            ->count('classe_id');

        $result['stats'] = [
            ...$result['stats'],
            'global_refresh' => true,
            'snapshots_scanned' => $scanned,
            'snapshots_refreshed' => $result['stats']['student_snapshots'] + $result['stats']['class_snapshots'],
            'stale_retries' => 0,
            'snapshots_remaining' => $remaining,
            'has_more' => $remaining > 0,
        ];

        return $result;
    }

    private function synchronizeClass(ESBTPClasse $classe, int $yearId, string $period, array &$stats): void
    {
        $academicSystem = $this->systems->normalize((string) $classe->systeme_academique);
        $studentIds = $this->activeStudentIds((int) $classe->id, $yearId);
        $stats['classes_processed']++;
        $stats['students_processed'] += $studentIds->count();
        $this->storeStudentSnapshots($studentIds, $classe, $yearId, $period, $academicSystem);
        $stats['alerts_seen'] += count($this->alerts->refreshClass(
            (int) $classe->id,
            $yearId,
            $academicSystem,
            $period,
        ));
        $this->openAlerts->flush();

        $this->storeStudentSnapshots($studentIds, $classe, $yearId, $period, $academicSystem);
        $stats['student_snapshots'] += $studentIds->count();

        $this->snapshots->storeClass(
            $yearId,
            $academicSystem,
            $period,
            $this->classHealth->evaluate((int) $classe->id, $yearId, $academicSystem, $period),
        );
        $stats['class_snapshots']++;
    }

    private function storeStudentSnapshots(
        Collection $studentIds,
        ESBTPClasse $classe,
        int $yearId,
        string $period,
        string $academicSystem,
    ): void {
        foreach ($studentIds as $studentId) {
            $context = new StudentMetricContext(
                (int) $studentId,
                (int) $classe->id,
                $yearId,
                $academicSystem,
                $period,
            );
            $this->snapshots->storeStudent($context, $this->studentHealth->evaluate($context));
        }
    }

    private function classesQuery(int $yearId, ?string $system, ?int $classId): Builder
    {
        return ESBTPClasse::query()
            ->without(['filiere', 'niveau', 'annee'])
            ->where('is_active', true)
            ->where('annee_universitaire_id', $yearId)
            ->when($classId, fn (Builder $query) => $query->whereKey($classId))
            ->when($system === AcademicSystemNormalizer::BTS, fn ($query) => $query->where(
                fn ($scope) => $scope
                    ->where('systeme_academique', AcademicSystemNormalizer::BTS)
                    ->orWhereNull('systeme_academique')
                    ->orWhere('systeme_academique', ''),
            ))
            ->when($system === AcademicSystemNormalizer::LMD, fn ($query) => $query->where(
                'systeme_academique',
                AcademicSystemNormalizer::LMD,
            ))
            ->orderBy('name')
            ->select(['id', 'name', 'code', 'systeme_academique']);
    }

    private function dirtySnapshotQuery(int $yearId, string $period, ?string $system): Builder
    {
        return AcademicMetricSnapshot::query()
            ->where('is_dirty', true)
            ->whereNotNull('classe_id')
            ->where('annee_universitaire_id', $yearId)
            ->where('semester', $period)
            ->whereHas('classe', fn (Builder $classes) => $classes
                ->where('is_active', true)
                ->where('annee_universitaire_id', $yearId))
            ->when($system, fn (Builder $query) => $query->where('academic_system', $system));
    }

    private function activeStudentIds(int $classId, int $yearId): Collection
    {
        return ESBTPInscription::query()
            ->where('classe_id', $classId)
            ->where('annee_universitaire_id', $yearId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->pluck('etudiant_id')
            ->unique()
            ->values();
    }

    private function emptyStats(): array
    {
        return [
            'classes_processed' => 0,
            'students_processed' => 0,
            'class_snapshots' => 0,
            'student_snapshots' => 0,
            'alerts_seen' => 0,
            'failed' => 0,
        ];
    }

    private function mergeStats(array &$stats, array $classStats): void
    {
        foreach ($classStats as $key => $value) {
            $stats[$key] += $value;
        }
    }

    private function classLabel(ESBTPClasse $classe): string
    {
        return trim(($classe->code ? "{$classe->code} · " : '').$classe->name);
    }
}
