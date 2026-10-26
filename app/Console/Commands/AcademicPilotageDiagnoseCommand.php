<?php

namespace App\Console\Commands;

use App\Domain\AcademicPilotage\Models\AcademicAlert;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Services\AcademicPilotageBackfillService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AcademicPilotageDiagnoseCommand extends Command
{
    protected $signature = 'academic-pilotage:diagnose
        {--year-id= : Limit diagnosis to one academic year}
        {--period= : Limit diagnosis to one period}
        {--class-id= : Limit diagnosis to one class}
        {--json : Output machine-readable JSON}';

    protected $description = 'Diagnose academic pilotage sheets, alerts, snapshots and score coverage.';

    public function handle(AcademicPilotageBackfillService $backfill): int
    {
        $filters = [
            'year_id' => $this->option('year-id'),
            'period' => $this->option('period'),
            'class_id' => $this->option('class-id'),
        ];
        $diagnosis = [
            'backfill' => $backfill->diagnose($filters),
            'grade_sheets' => $this->gradeSheets($filters),
            'alerts' => $this->alerts($filters),
            'snapshots' => $this->snapshots($filters),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($diagnosis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->components->info('Academic pilotage diagnosis');
        $this->table(['Scope', 'Count'], [
            ['Eligible evaluations', $diagnosis['backfill']['eligible_evaluations']],
            ['Existing grade sheets', $diagnosis['backfill']['existing_grade_sheets']],
            ['Open alerts', $diagnosis['alerts']['open']],
            ['Dirty snapshots', $diagnosis['snapshots']['dirty']],
            ['Insufficient score coverage', $diagnosis['snapshots']['insufficient_data']],
        ]);

        return self::SUCCESS;
    }

    private function gradeSheets(array $filters): array
    {
        $query = GradeSheet::query()
            ->when($filters['year_id'] ?? null, fn ($scope, $yearId) => $scope->where('annee_universitaire_id', (int) $yearId))
            ->when($filters['period'] ?? null, fn ($scope, $period) => $scope->where('semester', (string) $period))
            ->when($filters['class_id'] ?? null, fn ($scope, $classId) => $scope->where('classe_id', (int) $classId));

        return [
            'total' => (clone $query)->count(),
            'by_status' => (clone $query)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
        ];
    }

    private function alerts(array $filters): array
    {
        $query = AcademicAlert::query()
            ->when($filters['year_id'] ?? null, fn ($scope, $yearId) => $scope->where('annee_universitaire_id', (int) $yearId))
            ->when($filters['period'] ?? null, fn ($scope, $period) => $scope->where('semester', (string) $period))
            ->when($filters['class_id'] ?? null, fn ($scope, $classId) => $scope->where('classe_id', (int) $classId));

        return [
            'total' => (clone $query)->count(),
            'open' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
            'blocking' => (clone $query)->where('severity', 'blocking')->count(),
        ];
    }

    private function snapshots(array $filters): array
    {
        $query = AcademicMetricSnapshot::query()
            ->when($filters['year_id'] ?? null, fn ($scope, $yearId) => $scope->where('annee_universitaire_id', (int) $yearId))
            ->when($filters['period'] ?? null, fn ($scope, $period) => $scope->where('semester', (string) $period))
            ->when($filters['class_id'] ?? null, fn ($scope, $classId) => $scope->where('classe_id', (int) $classId));

        return [
            'total' => (clone $query)->count(),
            'dirty' => (clone $query)->where('is_dirty', true)->count(),
            'insufficient_data' => (clone $query)->where('level', 'insufficient_data')->count(),
            'average_coverage_pct' => round((float) (clone $query)->avg('coverage_pct'), 2),
            'failed_samples' => (clone $query)
                ->whereNotNull('last_refresh_error')
                ->latest('updated_at')
                ->limit(10)
                ->get([
                    'id',
                    'scope_type',
                    'scope_id',
                    'refresh_attempts',
                    'last_refresh_error',
                    'updated_at',
                ])
                ->map(static fn (AcademicMetricSnapshot $snapshot) => [
                    'id' => (int) $snapshot->id,
                    'scope_type' => (string) $snapshot->scope_type,
                    'scope_id' => (int) $snapshot->scope_id,
                    'refresh_attempts' => (int) $snapshot->refresh_attempts,
                    'error_fingerprint' => substr(hash('sha256', (string) $snapshot->last_refresh_error), 0, 16),
                    'updated_at' => $snapshot->updated_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }
}
