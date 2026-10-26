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
            ['Cohort pairs (total)', $diagnosis['cohorte']['couples_cohorte'] ?? 'n/a'],
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
     * Cohorte : ESBTPInscription::cohorteAcademique (inscriptions actives, étudiant créé),
     * limitée aux classes actives (is_active). Pas de filtre d'année sur la classe.
     * Instantanés : AcademicMetricSnapshot::pourCohorte, puis ->exploitable() pour les
     * lignes propres, comme AcademicMetricSnapshotCohortProvider. Une ligne propre prime.
     */
    private function cohorte(array $filters): array
    {
        $brutPeriode = filled($filters['period'] ?? null) ? (string) $filters['period'] : 'semestre1';
        try {
            $periode = app(AcademicPeriodNormalizer::class)->normalize($brutPeriode);
        } catch (InvalidArgumentException) {
            return $this->cohorteSansMesure('période non reconnue : rien n\'a été compté', null);
        }

        $brutAnnee = $filters['year_id'] ?? null;
        if (filled($brutAnnee) && ! $this->identifiantValide($brutAnnee)) {
            return $this->cohorteSansMesure('identifiant d\'année non valide : rien n\'a été compté', $periode);
        }
        $anneeId = filled($brutAnnee) ? (int) $brutAnnee : (int) (ESBTPAnneeUniversitaire::anneeCourante()?->id ?? 0);
        if ($anneeId <= 0) {
            return $this->cohorteSansMesure('aucune année universitaire courante : rien n\'a été compté', $periode);
        }

        $brutClasse = $filters['class_id'] ?? null;
        if (filled($brutClasse) && ! $this->identifiantValide($brutClasse)) {
            return $this->cohorteSansMesure('identifiant de classe non valide : rien n\'a été compté', $periode);
        }
        $classeId = filled($brutClasse) ? (int) $brutClasse : null;

        // Les soft-deletes d'inscription et de classe sont appliqués par Eloquent, y compris dans whereHas.
        $couples = ESBTPInscription::query()
            ->cohorteAcademique($anneeId, $classeId)
            ->whereHas('classe', fn ($classe) => $classe->where('is_active', true))
            ->with(['classe' => fn ($classe) => $classe->without(['filiere', 'niveau', 'annee'])
                ->select(['id', 'systeme_academique'])])
            ->get(['id', 'etudiant_id', 'classe_id'])
            ->unique(fn ($inscription): string => $inscription->classe_id.'|'.$inscription->etudiant_id);

        $systemes = app(AcademicSystemNormalizer::class);
        $compteurs = ['exploitable' => 0, 'sans_ligne' => 0, 'ligne_sale' => 0, 'systeme_non_classable' => 0];

        foreach ($couples->groupBy('classe_id') as $classeCourante => $couplesDeClasse) {
            try {
                $systeme = $systemes->normalize($couplesDeClasse->first()->classe->systeme_academique);
            } catch (InvalidArgumentException) {
                $compteurs['systeme_non_classable'] += $couplesDeClasse->count();

                continue;
            }

            $lecteur = fn () => AcademicMetricSnapshot::query()
                ->pourCohorte((int) $classeCourante, $anneeId, $systeme, $periode);
            $presents = $lecteur()->distinct()->pluck('etudiant_id')->map(fn ($id): int => (int) $id)->flip();
            $propres = $lecteur()->exploitable()->distinct()->pluck('etudiant_id')->map(fn ($id): int => (int) $id)->flip();

            foreach ($couplesDeClasse as $couple) {
                $etudiant = (int) $couple->etudiant_id;
                if (! $presents->has($etudiant)) {
                    $compteurs['sans_ligne']++;
                } elseif ($propres->has($etudiant)) {
                    $compteurs['exploitable']++;
                } else {
                    $compteurs['ligne_sale']++;
                }
            }
        }

        return [
            'annee_universitaire_id' => $anneeId,
            'period' => $periode,
            'perimetre' => 'inscriptions actives et étudiant créé, classes actives (is_active), sans filtre d\'année sur la classe',
            'motif' => null,
            'couples_cohorte' => $couples->count(),
            'etudiants_distincts' => $couples->pluck('etudiant_id')->unique()->count(),
            'exploitable' => $compteurs['exploitable'],
            'sans_ligne' => $compteurs['sans_ligne'],
            'ligne_sale' => $compteurs['ligne_sale'],
            'systeme_non_classable' => $compteurs['systeme_non_classable'],
            'sans_instantane_exploitable' => $compteurs['sans_ligne'] + $compteurs['ligne_sale'],
        ];
    }

    /** Vide = absent (géré par l'appelant) ; un identifiant doit être un entier strictement positif. */
    private function identifiantValide(mixed $valeur): bool
    {
        return ctype_digit((string) $valeur) && (int) $valeur > 0;
    }

    /** @return array<string, mixed> */
    private function cohorteSansMesure(string $motif, ?string $periode): array
    {
        return [
            'annee_universitaire_id' => null,
            'period' => $periode,
            'perimetre' => 'inscriptions actives et étudiant créé, classes actives (is_active), sans filtre d\'année sur la classe',
            'motif' => $motif,
            'couples_cohorte' => null,
            'etudiants_distincts' => null,
            'exploitable' => null,
            'sans_ligne' => null,
            'ligne_sale' => null,
            'systeme_non_classable' => null,
            'sans_instantane_exploitable' => null,
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
