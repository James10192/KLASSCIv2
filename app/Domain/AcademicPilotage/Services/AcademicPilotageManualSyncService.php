<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AcademicPilotageManualSyncService
{
    public function __construct(
        private readonly StudentAcademicHealthService $studentHealth,
        private readonly ClassAcademicHealthService $classHealth,
        private readonly AcademicMetricSnapshotService $snapshots,
        private readonly AcademicAlertDetectionService $alerts,
        private readonly AcademicSystemNormalizer $systems,
    ) {}

    public function synchronize(int $yearId, string $period, ?string $system, int $classId): array
    {
        $classes = $this->classesQuery($yearId, $system, $classId)
            ->limit(1)
            ->get();

        $stats = $this->emptyStats();
        $failures = [];

        foreach ($classes as $classe) {
            try {
                $this->synchronizeClass($classe, $yearId, $period, $stats);
            } catch (Throwable $exception) {
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

    private function synchronizeClass(ESBTPClasse $classe, int $yearId, string $period, array &$stats): void
    {
        $academicSystem = $this->systems->normalize((string) $classe->systeme_academique);
        $studentIds = $this->activeStudentIds((int) $classe->id, $yearId);
        $stats['classes_processed']++;
        $stats['students_processed'] += $studentIds->count();

        foreach ($studentIds as $studentId) {
            $context = new StudentMetricContext((int) $studentId, (int) $classe->id, $yearId, $academicSystem, $period);
            $this->snapshots->storeStudent($context, $this->studentHealth->evaluate($context));
            $stats['student_snapshots']++;
        }

        $this->snapshots->storeClass(
            $yearId,
            $academicSystem,
            $period,
            $this->classHealth->evaluate((int) $classe->id, $yearId, $academicSystem, $period),
        );
        $stats['class_snapshots']++;
        $stats['alerts_seen'] += count($this->alerts->refreshClass((int) $classe->id, $yearId, $academicSystem, $period));
    }

    private function classesQuery(int $yearId, ?string $system, int $classId): Builder
    {
        return ESBTPClasse::query()
            ->without(['filiere', 'niveau', 'annee'])
            ->where('is_active', true)
            ->where('annee_universitaire_id', $yearId)
            ->whereKey($classId)
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

    private function classLabel(ESBTPClasse $classe): string
    {
        return trim(($classe->code ? "{$classe->code} · " : '').$classe->name);
    }
}
