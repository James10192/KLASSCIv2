<?php

namespace App\Console\Commands;

use App\Domain\AcademicPilotage\Models\AcademicAlert;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Domain\AcademicPilotage\Services\AcademicPilotageBackfillService;
use App\Domain\AcademicPilotage\Services\AcademicSystemNormalizer;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
            ['Cohort scope', $diagnosis['cohorte']['motif'] ?? sprintf('year %s, period %s', $diagnosis['cohorte']['annee_universitaire_id'], $diagnosis['cohorte']['period'])],
            ['Cohort pairs without usable snapshot', $diagnosis['cohorte']['sans_instantane_exploitable'] ?? 'n/a'],
            ['  of which: no snapshot row', $diagnosis['cohorte']['sans_ligne'] ?? 'n/a'],
            ['  of which: dirty snapshot only', $diagnosis['cohorte']['ligne_sale'] ?? 'n/a'],
            ['  of which: system not classable', $diagnosis['cohorte']['systeme_non_classable'] ?? 'n/a'],
        ]);

        return self::SUCCESS;
    }

    /**
     * Couples (classe, étudiant) de la cohorte sans instantané exploitable.
     *
     * La cohorte est le scope partagé ESBTPInscription::cohorteAcademique (la même
     * que ClassAcademicHealthService). Les lignes d'instantané suivent les filtres
     * d'AcademicMetricSnapshotCohortProvider : portée étudiant, période, moteur,
     * système académique. Une ligne propre prime sur une ligne sale.
     */
    private function cohorte(array $filters): array
    {
        $periode = app(AcademicPeriodNormalizer::class)->normalize($filters['period'] ?? 'semestre1');
        $anneeId = (int) ($filters['year_id'] ?? ESBTPAnneeUniversitaire::anneeCourante()?->id ?? 0);
        $classeId = isset($filters['class_id']) ? (int) $filters['class_id'] : null;

        if ($anneeId <= 0) {
            return [
                'annee_universitaire_id' => null,
                'period' => $periode,
                'motif' => 'aucune année universitaire courante : rien n\'a été compté',
                'couples_cohorte' => null,
                'etudiants_distincts' => null,
                'exploitable' => null,
                'sans_ligne' => null,
                'ligne_sale' => null,
                'systeme_non_classable' => null,
                'sans_instantane_exploitable' => null,
            ];
        }

        // Les soft-deletes d'inscription et de classe sont appliqués par Eloquent,
        // y compris dans whereHas : une inscription ou une classe supprimée sort de la cohorte.
        $couples = ESBTPInscription::query()
            ->cohorteAcademique($anneeId, $classeId)
            ->whereHas('classe', fn ($classe) => $classe->where('is_active', true))
            ->with('classe:id,systeme_academique')
            ->get(['id', 'etudiant_id', 'classe_id'])
            ->unique(fn ($inscription): string => $inscription->classe_id.'|'.$inscription->etudiant_id);

        $lignes = AcademicMetricSnapshot::query()
            ->where('scope_type', 'student')
            ->where('annee_universitaire_id', $anneeId)
            ->where('semester', $periode)
            ->where('engine_version', (string) config('academic_pilotage.engine_version', '1.0.0'))
            ->when($classeId !== null, fn ($scope) => $scope->where('classe_id', $classeId))
            ->get(['classe_id', 'etudiant_id', 'academic_system', 'is_dirty'])
            ->groupBy(fn ($ligne): string => $ligne->classe_id.'|'.$ligne->etudiant_id.'|'.$ligne->academic_system);

        $systemes = app(AcademicSystemNormalizer::class);
        $exploitable = 0;
        $sansLigne = 0;
        $ligneSale = 0;
        $nonClassable = 0;

        foreach ($couples as $couple) {
            try {
                $systeme = $systemes->normalize($couple->classe->systeme_academique);
            } catch (InvalidArgumentException) {
                $nonClassable++;

                continue;
            }

            $existantes = $lignes->get($couple->classe_id.'|'.$couple->etudiant_id.'|'.$systeme);
            if ($existantes === null) {
                $sansLigne++;
            } elseif ($existantes->contains(fn ($ligne): bool => ! $ligne->is_dirty)) {
                $exploitable++;
            } else {
                $ligneSale++;
            }
        }

        return [
            'annee_universitaire_id' => $anneeId,
            'period' => $periode,
            'motif' => null,
            'couples_cohorte' => $couples->count(),
            'etudiants_distincts' => $couples->pluck('etudiant_id')->unique()->count(),
            'exploitable' => $exploitable,
            'sans_ligne' => $sansLigne,
            'ligne_sale' => $ligneSale,
            'systeme_non_classable' => $nonClassable,
            'sans_instantane_exploitable' => $sansLigne + $ligneSale,
        ];
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
