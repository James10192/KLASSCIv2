<?php

namespace App\Console\Commands;

use App\Domain\AcademicPilotage\Models\AcademicAlert;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Domain\AcademicPilotage\Services\AcademicPilotageBackfillService;
use App\Domain\AcademicPilotage\Services\AcademicSystemNormalizer;
use App\Models\ESBTPAnneeUniversitaire;
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
            'cohorte' => $this->cohorte($filters),
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
            ['Cohort pairs without usable snapshot', $diagnosis['cohorte']['sans_instantane_exploitable']],
            ['  of which: no snapshot row', $diagnosis['cohorte']['sans_ligne']],
            ['  of which: dirty snapshot', $diagnosis['cohorte']['ligne_sale']],
        ]);

        return self::SUCCESS;
    }

    /**
     * Compte les couples (classe, étudiant) de la cohorte qui n'ont aucun instantané
     * exploitable, avec les mêmes filtres que AcademicMetricSnapshotCohortProvider.
     * Un étudiant sans ligne n'apparaît dans aucun autre compte du diagnostic.
     */
    private function cohorte(array $filters): array
    {
        $anneeId = (int) ($filters['year_id'] ?? $this->anneeCourante());
        $periode = app(AcademicPeriodNormalizer::class)->normalize($filters['period'] ?? 'semestre1');
        $systemes = app(AcademicSystemNormalizer::class);

        if ($anneeId <= 0) {
            return ['annee_universitaire_id' => null, 'period' => $periode] + array_fill_keys([
                'couples_cohorte', 'etudiants_distincts', 'exploitable', 'sans_ligne', 'ligne_sale',
                'sans_instantane_exploitable',
            ], 0);
        }

        $couples = DB::table('esbtp_inscriptions as i')
            ->join('esbtp_classes as c', 'c.id', '=', 'i.classe_id')
            ->where('i.annee_universitaire_id', $anneeId)
            ->where('i.status', 'active')
            ->where('i.workflow_step', 'etudiant_cree')
            ->where('c.is_active', true)
            ->when($filters['class_id'] ?? null, fn ($scope, $classId) => $scope->where('i.classe_id', (int) $classId))
            ->distinct()
            ->get(['i.classe_id', 'i.etudiant_id', 'c.systeme_academique']);

        // Lignes que le lecteur peut voir : même année, période, moteur et portée « étudiant ».
        $lignes = AcademicMetricSnapshot::query()
            ->where('scope_type', 'student')
            ->where('annee_universitaire_id', $anneeId)
            ->where('semester', $periode)
            ->where('engine_version', (string) config('academic_pilotage.engine_version', '1.0.0'))
            ->when($filters['class_id'] ?? null, fn ($scope, $classId) => $scope->where('classe_id', (int) $classId))
            ->get(['classe_id', 'etudiant_id', 'academic_system', 'is_dirty'])
            ->groupBy(fn ($ligne): string => $ligne->classe_id.'|'.$ligne->etudiant_id.'|'.$ligne->academic_system);

        $exploitable = 0;
        $sansLigne = 0;
        $ligneSale = 0;
        foreach ($couples as $couple) {
            $cle = $couple->classe_id.'|'.$couple->etudiant_id.'|'.$systemes->normalize($couple->systeme_academique);
            $existantes = $lignes->get($cle);
            if ($existantes === null) {
                $sansLigne++;
            } elseif ($existantes->contains(fn ($ligne): bool => (bool) $ligne->is_dirty)) {
                $ligneSale++;
            } else {
                $exploitable++;
            }
        }

        return [
            'annee_universitaire_id' => $anneeId,
            'period' => $periode,
            'couples_cohorte' => $couples->count(),
            'etudiants_distincts' => $couples->pluck('etudiant_id')->unique()->count(),
            'exploitable' => $exploitable,
            'sans_ligne' => $sansLigne,
            'ligne_sale' => $ligneSale,
            'sans_instantane_exploitable' => $sansLigne + $ligneSale,
        ];
    }

    private function anneeCourante(): ?int
    {
        return ESBTPAnneeUniversitaire::query()
            ->where('is_active', true)
            ->latest('annee_debut')
            ->value('id');
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
            'ouvertes_par_type' => (clone $query)
                ->whereIn('status', ['open', 'acknowledged', 'in_progress'])
                ->selectRaw('type, count(*) as total')
                ->groupBy('type')
                ->orderByDesc('total')
                ->pluck('total', 'type')
                ->all(),
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
