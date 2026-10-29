<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPNote;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPSeanceCours;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AcademicMetricSnapshotInvalidationService
{
    private ?bool $snapshotsAvailable = null;

    private ?bool $sourceRevisionAvailable = null;

    public function __construct(private readonly AcademicPeriodNormalizer $periods) {}

    public function fromNote(ESBTPNote $note): void
    {
        $this->schedule(fn (): array => [
            $this->noteContext($note, true),
            $this->noteContext($note, false),
        ], 'note', (int) $note->getKey());
    }

    public function fromEvaluation(ESBTPEvaluation $evaluation): void
    {
        $this->schedule(fn (): array => [
            $this->modelContext($evaluation, true),
            $this->modelContext($evaluation, false),
        ], 'evaluation', (int) $evaluation->getKey());
    }

    public function fromAttendance(ESBTPAttendance $attendance): void
    {
        $this->schedule(fn (): array => [
            $this->attendanceContext($attendance, true),
            $this->attendanceContext($attendance, false),
        ], 'attendance', (int) $attendance->getKey());
    }

    public function fromGradeSheet(GradeSheet $sheet): void
    {
        $this->schedule(fn (): array => $this->gradeSheetContexts($sheet), 'grade_sheet', (int) $sheet->getKey());
    }

    public function fromLmdBulletin(ESBTPLMDBulletin $bulletin): void
    {
        $this->schedule(fn (): array => [
            $this->modelContext($bulletin, true, 'semestre', 'etudiant_id'),
            $this->modelContext($bulletin, false, 'semestre', 'etudiant_id'),
        ], 'lmd_bulletin', (int) $bulletin->getKey());
    }

    public function fromEnrollment(ESBTPInscription $enrollment): void
    {
        $this->scheduleAllPeriods(fn (): array => [
            $this->scopeContext($enrollment, true, 'etudiant_id'),
            $this->scopeContext($enrollment, false, 'etudiant_id'),
        ], 'enrollment', (int) $enrollment->getKey());
    }

    public function fromPlanning(ESBTPPlanificationAcademique $planning): void
    {
        if (! $this->snapshotsAreAvailable()) {
            return;
        }

        try {
            $scopes = $this->uniquePlanningScopes([
                $this->planningScope($planning, true),
                $this->planningScope($planning, false),
            ]);

            DB::afterCommit(function () use ($scopes, $planning): void {
                foreach ($scopes as $scope) {
                    $this->invalidatePlanningScope($scope, (int) $planning->getKey());
                }
            });
        } catch (Throwable $exception) {
            $this->logFailure($exception, 'academic_planning', (int) $planning->getKey());
        }
    }

    public function invalidateAfterCommit(
        int $classId,
        int $academicYearId,
        string $period,
        ?int $studentId = null,
        string $source = 'business_mutation',
    ): void {
        $this->afterCommit([compact(
            'classId',
            'academicYearId',
            'period',
            'studentId',
        )], $source);
    }

    public function invalidateManyAfterCommit(array $contexts, string $source = 'business_mutation'): void
    {
        $this->afterCommit($contexts, $source);
    }

    public function invalidate(
        int $classId,
        int $academicYearId,
        string $period,
        ?int $studentId = null,
    ): int {
        if ($classId <= 0 || $academicYearId <= 0 || trim($period) === '') {
            return 0;
        }

        try {
            $period = $this->periods->normalize($period);
        } catch (Throwable $exception) {
            Log::warning('Academic metric invalidation skipped for unsupported period.', [
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
                'period' => $period,
            ]);

            return 0;
        }

        $this->oublierLaCouverture($classId, $academicYearId, $period);

        return AcademicMetricSnapshot::query()
            ->where('classe_id', $classId)
            ->where('annee_universitaire_id', $academicYearId)
            ->where('semester', $period)
            ->when($studentId !== null, fn ($query) => $query->where(
                fn ($scope) => $scope->where('scope_type', 'class')
                    ->orWhere('etudiant_id', $studentId),
            ))
            ->update($this->dirtyPayload());
    }

    /**
     * Oublie la couverture des notes mise en cache pour cette classe.
     *
     * Appelee depuis `invalidate()`, elle-meme jouee dans `DB::afterCommit` :
     * vider le cache AVANT la validation en base laisserait une lecture
     * concurrente le remplir aussitot avec l'etat d'avant, et la couverture
     * resterait perimee jusqu'a expiration sans que rien ne le signale.
     *
     * La periode annuelle depend des deux semestres : une note du semestre 1
     * change aussi ce que l'annuel raconte, donc les trois sont oubliees.
     */
    private function oublierLaCouverture(int $classId, int $academicYearId, string $period): void
    {
        $periodes = [$period, 'annuel'];

        foreach (array_unique($periodes) as $periode) {
            Cache::forget(
                \App\Http\Controllers\AcademicPilotage\AcademicCoverageController::cle($classId, $academicYearId, $periode)
            );
        }
    }

    private function afterCommit(array $contexts, string $source, ?int $modelId = null): void
    {
        if (! $this->snapshotsAreAvailable()) {
            return;
        }

        $contexts = $this->uniqueContexts($contexts);
        if ($contexts === []) {
            return;
        }

        try {
            DB::afterCommit(function () use ($contexts, $source, $modelId): void {
                foreach ($contexts as $context) {
                    $this->invalidateSafely($context, $source, $modelId);
                }
            });
        } catch (Throwable $exception) {
            $this->logFailure($exception, $source, $modelId);
        }
    }

    private function schedule(callable $resolveContexts, string $source, ?int $modelId): void
    {
        if (! $this->snapshotsAreAvailable()) {
            return;
        }

        try {
            $this->afterCommit($resolveContexts(), $source, $modelId);
        } catch (Throwable $exception) {
            $this->logFailure($exception, $source, $modelId);
        }
    }

    private function scheduleAllPeriods(callable $resolveContexts, string $source, ?int $modelId): void
    {
        if (! $this->snapshotsAreAvailable()) {
            return;
        }

        try {
            $contexts = $this->uniqueScopeContexts($resolveContexts());
            DB::afterCommit(function () use ($contexts, $source, $modelId): void {
                foreach ($contexts as $context) {
                    try {
                        $this->invalidateAllPeriods(...$context);
                    } catch (Throwable $exception) {
                        $this->logFailure($exception, $source, $modelId, $context);
                    }
                }
            });
        } catch (Throwable $exception) {
            $this->logFailure($exception, $source, $modelId);
        }
    }

    private function invalidateSafely(array $context, string $source, ?int $modelId): void
    {
        try {
            $this->invalidate(...$context);
        } catch (Throwable $exception) {
            $this->logFailure($exception, $source, $modelId, $context);
        }
    }

    private function noteContext(ESBTPNote $note, bool $original): array
    {
        $evaluationId = (int) $this->attribute($note, 'evaluation_id', $original);
        $evaluation = $evaluationId > 0
            ? ESBTPEvaluation::withTrashed()->find($evaluationId)
            : null;

        return [
            'classId' => (int) ($evaluation?->classe_id
                ?? $this->attribute($note, 'classe_id', $original)),
            'academicYearId' => (int) ($evaluation?->annee_universitaire_id
                ?? $this->attribute($note, 'annee_universitaire_id', $original)
                ?? $this->attribute($note, 'annee_universitaire', $original)),
            'period' => (string) ($evaluation?->periode
                ?? $this->attribute($note, 'semestre', $original)),
            'studentId' => (int) $this->attribute($note, 'etudiant_id', $original),
        ];
    }

    private function attendanceContext(ESBTPAttendance $attendance, bool $original): array
    {
        $sessionId = (int) $this->attribute($attendance, 'seance_cours_id', $original);
        $semester = ESBTPSeanceCours::withTrashed()
            ->with('emploiTemps')
            ->find($sessionId)?->emploiTemps?->semestre;

        return array_merge($this->modelContext($attendance, $original), [
            'period' => (string) $semester,
            'studentId' => (int) $this->attribute($attendance, 'etudiant_id', $original),
        ]);
    }

    private function modelContext(
        Model $model,
        bool $original,
        string $periodAttribute = 'periode',
        ?string $studentAttribute = null,
    ): array {
        $context = [
            'classId' => (int) $this->attribute($model, 'classe_id', $original),
            'academicYearId' => (int) $this->attribute(
                $model,
                'annee_universitaire_id',
                $original,
            ),
            'period' => (string) $this->attribute($model, $periodAttribute, $original),
        ];

        if ($studentAttribute !== null) {
            $context['studentId'] = (int) $this->attribute($model, $studentAttribute, $original);
        }

        return $context;
    }

    private function gradeSheetContexts(GradeSheet $sheet): array
    {
        $baseContext = $this->modelContext($sheet, false, 'semester');
        $contexts = [$baseContext];

        $sheet->entries()
            ->select('etudiant_id')
            ->whereNotNull('etudiant_id')
            ->distinct()
            ->orderBy('etudiant_id')
            ->chunk(250, function ($entries) use (&$contexts, $baseContext): void {
                foreach ($entries as $entry) {
                    $contexts[] = $baseContext + ['studentId' => (int) $entry->etudiant_id];
                }
            });

        return $contexts;
    }

    private function scopeContext(Model $model, bool $original, ?string $studentAttribute = null): array
    {
        $context = [
            'classId' => (int) $this->attribute($model, 'classe_id', $original),
            'academicYearId' => (int) $this->attribute($model, 'annee_universitaire_id', $original),
        ];

        if ($studentAttribute !== null) {
            $context['studentId'] = (int) $this->attribute($model, $studentAttribute, $original);
        }

        return $context;
    }

    private function planningScope(ESBTPPlanificationAcademique $planning, bool $original): array
    {
        return [
            'academicYearId' => (int) $this->attribute($planning, 'annee_universitaire_id', $original),
            'filiereId' => (int) $this->attribute($planning, 'filiere_id', $original),
            'niveauId' => (int) $this->attribute($planning, 'niveau_etude_id', $original),
            'period' => (string) $this->attribute($planning, 'semestre', $original),
        ];
    }

    private function uniquePlanningScopes(array $scopes): array
    {
        $unique = [];

        foreach ($scopes as $scope) {
            if (($scope['academicYearId'] ?? 0) <= 0
                || ($scope['filiereId'] ?? 0) <= 0
                || ($scope['niveauId'] ?? 0) <= 0
                || trim((string) ($scope['period'] ?? '')) === '') {
                continue;
            }

            $unique[implode(':', $scope)] = $scope;
        }

        return array_values($unique);
    }

    private function invalidatePlanningScope(array $scope, int $modelId): void
    {
        DB::table('esbtp_classes')
            ->select('id')
            ->where('filiere_id', $scope['filiereId'])
            ->where('niveau_etude_id', $scope['niveauId'])
            ->orderBy('id')
            ->chunkById(250, function ($classes) use ($scope, $modelId): void {
                foreach ($classes as $class) {
                    $this->invalidateSafely([
                        'classId' => (int) $class->id,
                        'academicYearId' => (int) $scope['academicYearId'],
                        'period' => (string) $scope['period'],
                    ], 'academic_planning', $modelId);
                }
            });
    }

    private function attribute(Model $model, string $key, bool $original): mixed
    {
        return $original ? $model->getRawOriginal($key) : $model->getAttribute($key);
    }

    private function uniqueContexts(array $contexts): array
    {
        $unique = [];

        foreach ($contexts as $context) {
            if (($context['classId'] ?? 0) <= 0
                || ($context['academicYearId'] ?? 0) <= 0
                || trim((string) ($context['period'] ?? '')) === '') {
                continue;
            }

            $key = implode(':', $context);
            $unique[$key] = $context;
        }

        return array_values($unique);
    }

    private function uniqueScopeContexts(array $contexts): array
    {
        $unique = [];
        foreach ($contexts as $context) {
            if (($context['classId'] ?? 0) <= 0 || ($context['academicYearId'] ?? 0) <= 0) {
                continue;
            }
            $unique[implode(':', $context)] = $context;
        }

        return array_values($unique);
    }

    private function invalidateAllPeriods(
        int $classId,
        int $academicYearId,
        ?int $studentId = null,
    ): int {
        return AcademicMetricSnapshot::query()
            ->where('classe_id', $classId)
            ->where('annee_universitaire_id', $academicYearId)
            ->when($studentId !== null, fn ($query) => $query->where(
                fn ($scope) => $scope->where('scope_type', 'class')
                    ->orWhere('etudiant_id', $studentId),
            ))
            ->update($this->dirtyPayload());
    }

    private function snapshotsAreAvailable(): bool
    {
        return $this->snapshotsAvailable ??= Schema::hasTable('esbtp_academic_metric_snapshots');
    }

    private function sourceRevisionIsAvailable(): bool
    {
        return $this->sourceRevisionAvailable ??= Schema::hasColumn(
            'esbtp_academic_metric_snapshots',
            'source_revision',
        );
    }

    private function dirtyPayload(): array
    {
        $payload = [
            'is_dirty' => true,
            'stale_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_token')) {
            $payload['refresh_token'] = null;
        }
        if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_started_at')) {
            $payload['refresh_started_at'] = null;
        }
        if ($this->sourceRevisionIsAvailable()) {
            $payload['source_revision'] = DB::raw('COALESCE(source_revision, 0) + 1');
        }

        return $payload;
    }

    private function logFailure(
        Throwable $exception,
        string $source,
        ?int $modelId,
        array $context = [],
    ): void {
        try {
            Log::error('Academic metric snapshot invalidation failed.', [
                'source' => $source,
                'model_id' => $modelId,
                'context' => $context,
                'exception' => $exception,
            ]);
        } catch (Throwable) {
            // Invalidation and its telemetry must never block the business mutation.
        }
    }
}
