<?php

namespace App\Http\Controllers\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\AcademicResponsibility;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\AcademicAlert;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Presenters\GradeSheetSummaryPresenter;
use App\Domain\AcademicPilotage\Services\AcademicActorActivityService;
use App\Domain\AcademicPilotage\Services\AcademicPilotageManualSyncService;
use App\Domain\AcademicPilotage\Services\AcademicPilotageSummaryService;
use App\Domain\AcademicPilotage\Services\AcademicActorScopeService;
use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use App\Domain\AcademicPilotage\Services\AcademicPilotageTrendsService;
use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AcademicPilotageController extends Controller
{
    public function __construct(
        private readonly GradeSheetSummaryPresenter $gradeSheetSummary,
        private readonly AcademicActorScopeService $actorScope,
        private readonly AcademicActorActivityService $actorActivity,
        private readonly AcademicPilotageSummaryService $summary,
        private readonly AcademicNoteCoverageService $noteCoverage,
        private readonly AcademicPilotageTrendsService $trends,
    ) {}

    public function index(Request $request): View
    {
        $year = $this->selectedYear($request);
        $classes = $this->classOptions($year?->id, $this->actorScope->dashboardClassIds($request->user(), $year?->id));

        return view('esbtp.pilotage-academique.index', [
            'annees' => $this->yearOptions(),
            'selectedYear' => $year,
            'classes' => $classes,
            'periods' => $this->periodOptions(),
            'systems' => ['' => 'Tous les systèmes', 'BTS' => 'BTS', 'LMD' => 'LMD'],
            'assignmentUsers' => $request->user()->can('academic_sheets.assign')
                ? User::query()
                    ->where('is_active', true)
                    ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'etudiant'))
                    ->with('roles:id,name')
                    ->orderBy('name')
                    ->get(['id', 'name', 'email', 'username'])
                : collect(),
            'responsibilityOptions' => collect(AcademicResponsibility::cases())
                ->mapWithKeys(fn (AcademicResponsibility $responsibility) => [
                    $responsibility->value => $responsibility->label(),
                ])
                ->all(),
            'initialFilters' => [
                'year_id' => $year?->id,
                'period' => $request->input('period', 'semestre1'),
                'system' => $request->input('system', ''),
                'class_id' => $request->input('class_id', ''),
            ],
        ]);
    }

    /**
     * Tendances mensuelles de l'annee. Endpoint separe, charge a la demande :
     * quatre agregations sur toute l'annee n'ont pas a ralentir l'ouverture
     * de la page, qui doit repondre d'abord sur l'etat du jour.
     */
    public function trends(Request $request): JsonResponse
    {
        $year = $this->selectedYear($request);

        return response()->json(
            $this->trends->forYear($year?->id, $request->boolean('recalculer')),
        );
    }

    public function data(Request $request): JsonResponse
    {
        $year = $this->selectedYear($request);
        $period = $this->period($request);
        $system = $this->system($request);
        $classId = $request->integer('class_id') ?: null;
        $scope = $this->actorScope->dashboardScope($request->user(), $year?->id);
        $classIds = $scope->global ? null : $scope->classIds;

        if ($classId !== null && $classIds !== null && ! $classIds->contains($classId)) {
            abort(403);
        }

        return response()->json([
            'ok' => true,
            'filters' => [
                'year_id' => $year?->id,
                'period' => $period,
                'system' => $system,
                'class_id' => $classId,
            ],
            'prerequisites' => [
                'year_configured' => $year !== null,
                'message' => $year === null
                    ? 'Aucune année universitaire active. Activez une année avant de calculer le pilotage.'
                    : null,
            ],
            'summary' => $this->summary->summarize($year?->id, $period, $system, $classId, $classIds),
            'classes' => $this->classes($year?->id, $period, $system, $classId, 12, $classIds),
            'alerts' => $this->alerts($year?->id, $period, $classId, 10, null, $classIds),
            'sheets' => $this->sheets($year?->id, $period, $system, $classId, 10, $classIds, $request->user()),
            'note_coverage' => $this->noteCoverage->summarize($year?->id, $period, $system, $classId, $classIds),
            'my_sheets' => $this->sheets($year?->id, $period, $system, $classId, 8, $classIds, $request->user(), true),
            'students' => $this->students($year?->id, $period, $system, $classId, 10, $classIds),
            'actor_activity' => $this->actorActivity->summarize(
                $year?->id,
                $period,
                $system,
                $classId,
                $classIds,
                (int) $request->user()->id,
                $request->user()->name,
            ),
            'scope' => [
                'global' => $scope->global,
                'class_count' => $classIds?->count(),
                'sources' => $scope->sources,
            ],
            'freshness' => $this->freshness($year?->id, $period, $system, $classId, $classIds),
        ]);
    }

    public function synchronize(
        Request $request,
        AcademicPilotageManualSyncService $sync,
    ): JsonResponse {
        $year = $this->selectedYear($request);
        if ($year === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Aucune année universitaire active n’est disponible pour synchroniser le pilotage.',
            ], 422);
        }

        $period = $this->period($request);
        $system = $this->system($request);
        $classId = $request->integer('class_id') ?: null;
        $classIds = $this->actorScope->dashboardClassIds($request->user(), $year->id);
        if ($classId === null && $classIds !== null) {
            return response()->json([
                'ok' => false,
                'message' => 'Choisissez une classe de votre périmètre avant de synchroniser ses indicateurs.',
            ], 422);
        }

        abort_if($classIds !== null && ! $classIds->contains($classId), 403);

        $result = $sync->synchronize((int) $year->id, $period, $system, $classId);
        $stats = $result['stats'];

        return response()->json([
            'ok' => $stats['failed'] === 0,
            'message' => $stats['failed'] === 0
                ? $this->syncSuccessMessage($stats)
                : 'Synchronisation terminée avec des erreurs sur certaines classes.',
            'filters' => [
                'year_id' => (int) $year->id,
                'period' => $period,
                'system' => $system,
                'class_id' => $classId,
            ],
            'sync' => $stats,
            'failures' => $result['failures'],
        ], $stats['failed'] === 0 ? 200 : 207);
    }

    public function classHealth(Request $request, ESBTPClasse $classe): JsonResponse
    {
        $year = $this->selectedYear($request);
        $period = $this->period($request);
        $classIds = $this->actorScope->dashboardClassIds($request->user(), $year?->id);
        abort_if($classIds !== null && ! $classIds->contains($classe->id), 403);
        $snapshot = $this->scopeClasses($this->snapshotQuery($year?->id, $period, null, (int) $classe->id), $classIds)
            ->where('scope_type', 'class')
            ->latest('calculated_at')
            ->first();

        return response()->json([
            'ok' => true,
            'classe' => $this->classLabel($classe),
            'health' => $this->snapshotPayload($snapshot),
            'alerts' => $this->alerts($year?->id, $period, (int) $classe->id, 8, null, $classIds),
            'sheets' => $this->sheets($year?->id, $period, null, (int) $classe->id, 8, $classIds, $request->user()),
            'note_coverage' => $this->noteCoverage->summarize($year?->id, $period, null, (int) $classe->id, $classIds),
            'students' => $this->students($year?->id, $period, null, (int) $classe->id, 8, $classIds),
        ]);
    }

    public function studentHealth(Request $request, ESBTPEtudiant $etudiant): JsonResponse
    {
        $year = $this->selectedYear($request);
        $period = $this->period($request);
        $classIds = $this->actorScope->dashboardClassIds($request->user(), $year?->id);
        if ($classIds !== null) {
            $studentInScope = $year !== null && ESBTPInscription::query()
                ->where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $year->id)
                ->where('status', 'active')
                ->whereIn('classe_id', $classIds)
                ->exists();
            abort_unless($studentInScope, 404);
        }
        $snapshot = $this->scopeClasses($this->snapshotQuery($year?->id, $period), $classIds)
            ->where('scope_type', 'student')
            ->where('etudiant_id', $etudiant->id)
            ->latest('calculated_at')
            ->first();

        return response()->json([
            'ok' => true,
            'student' => [
                'id' => $etudiant->id,
                'name' => trim($etudiant->nom.' '.$etudiant->prenoms),
                'matricule' => $etudiant->matricule,
            ],
            'health' => $this->snapshotPayload($snapshot),
            'alerts' => $this->alerts($year?->id, $period, null, 8, (int) $etudiant->id, $classIds),
        ]);
    }

    private function classes(?int $yearId, string $period, ?string $system, ?int $classId, int $limit = 12, ?Collection $classIds = null): array
    {
        return $this->scopeClasses($this->snapshotQuery($yearId, $period, $system, $classId), $classIds)
            ->where('scope_type', 'class')
            ->with('classe:id,name,code,systeme_academique')
            ->latest('calculated_at')
            ->limit($limit)
            ->get()
            ->map(fn (AcademicMetricSnapshot $snapshot): array => [
                'id' => $snapshot->classe_id,
                'name' => $snapshot->classe ? $this->classLabel($snapshot->classe) : 'Classe supprimée',
                'system' => $snapshot->academic_system,
                'academic_score' => $snapshot->academic_score === null ? null : (float) $snapshot->academic_score,
                'operational_score' => $snapshot->operational_score === null ? null : (float) $snapshot->operational_score,
                'coverage_pct' => $snapshot->coverage_pct,
                'confidence_pct' => $snapshot->confidence_pct,
                'level' => $snapshot->level,
                'is_dirty' => $snapshot->is_dirty,
                'calculated_at' => optional($snapshot->calculated_at)->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function alerts(?int $yearId, string $period, ?int $classId = null, int $limit = 10, ?int $studentId = null, ?Collection $classIds = null): array
    {
        return $this->scopeClasses($this->alertQuery($yearId, $period, $classId), $classIds)
            ->when($studentId, fn ($query) => $query->where('etudiant_id', $studentId))
            ->with(['classe:id,name,code', 'etudiant:id,nom,prenoms,matricule'])
            ->latest('last_seen_at')
            ->limit($limit)
            ->get()
            ->map(fn (AcademicAlert $alert): array => [
                'id' => $alert->id,
                'type' => $alert->type,
                'severity' => $alert->severity?->value ?? (string) $alert->severity,
                'severity_label' => $alert->severity?->label() ?? (string) $alert->severity,
                'status' => $alert->status?->value ?? (string) $alert->status,
                'status_label' => $alert->status?->label() ?? (string) $alert->status,
                'message' => $alert->message,
                'recommended_action' => $alert->recommended_action,
                'classe' => $alert->classe ? $this->classLabel($alert->classe) : null,
                'student' => $alert->etudiant ? trim($alert->etudiant->nom.' '.$alert->etudiant->prenoms) : null,
                'last_seen_at' => optional($alert->last_seen_at)->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function sheets(
        ?int $yearId,
        string $period,
        ?string $system,
        ?int $classId,
        int $limit,
        ?Collection $classIds,
        User $actor,
        bool $actorOnly = false,
    ): array
    {
        $query = $this->scopeClasses($this->sheetQuery($yearId, $period, $system, $classId), $classIds);
        if ($actorOnly) {
            $this->scopeActorSheets($query, $actor);
        }

        $sheets = $query
            ->with([
                'assignedProcessor:id,name,email',
                'classe:id,name,code',
                'controlledBy:id,name,email',
                'enteredBy:id,name,email',
                'latestEvent.actor:id,name,email',
                'matiere:id,name,code',
                'receivedBy:id,name,email',
                'submittedBy:id,name,email',
                'teacher:id,user_id',
                'teacher.user:id,first_name,last_name,name',
                'validatedBy:id,name,email',
            ])
            ->withCount([
                'entries',
                'entries as entered_entries_count' => fn ($query) => $query->where('status', 'entered'),
                'entries as resolved_entries_count' => fn ($query) => $query->whereIn('status', ['entered', 'absent', 'exempt', 'not_applicable']),
            ])
            ->latest('updated_at')
            ->limit($limit)
            ->get();

        return $this->gradeSheetSummary->collection($sheets, $actor);
    }

    private function scopeActorSheets(Builder $query, User $actor): void
    {
        $teacherId = $actor->teacherProfile?->getKey();
        $actorColumns = ['assigned_processor_id', 'submitted_by', 'received_by', 'entered_by', 'controlled_by', 'validated_by'];

        $query->where(function (Builder $scope) use ($actor, $actorColumns, $teacherId): void {
            foreach ($actorColumns as $index => $column) {
                $index === 0
                    ? $scope->where($column, $actor->id)
                    : $scope->orWhere($column, $actor->id);
            }
            $scope->when($teacherId, fn (Builder $builder) => $builder->orWhere('teacher_id', $teacherId));
            $scope->orWhereHas('evaluation', fn (Builder $evaluation) => $evaluation->where('enseignant_id', $actor->id));
        });
    }

    private function students(?int $yearId, string $period, ?string $system, ?int $classId, int $limit = 10, ?Collection $classIds = null): array
    {
        return $this->scopeClasses($this->snapshotQuery($yearId, $period, $system, $classId), $classIds)
            ->where('scope_type', 'student')
            ->with('etudiant:id,nom,prenoms,matricule')
            ->orderByRaw('academic_score is null asc')
            ->orderBy('academic_score')
            ->limit($limit)
            ->get()
            ->map(fn (AcademicMetricSnapshot $snapshot): array => [
                'id' => $snapshot->etudiant_id,
                'name' => $snapshot->etudiant ? trim($snapshot->etudiant->nom.' '.$snapshot->etudiant->prenoms) : 'Étudiant supprimé',
                'matricule' => $snapshot->etudiant?->matricule,
                'score' => $snapshot->academic_score === null ? null : (float) $snapshot->academic_score,
                'coverage_pct' => $snapshot->coverage_pct,
                'confidence_pct' => $snapshot->confidence_pct,
                'level' => $snapshot->level,
            ])
            ->values()
            ->all();
    }

    private function snapshotQuery(?int $yearId, string $period, ?string $system = null, ?int $classId = null): Builder
    {
        return AcademicMetricSnapshot::query()
            ->where('annee_universitaire_id', $yearId)
            ->where('semester', $period)
            ->when($system, fn ($query) => $query->where('academic_system', $system))
            ->when($classId, fn ($query) => $query->where('classe_id', $classId));
    }

    private function alertQuery(?int $yearId, string $period, ?int $classId = null): Builder
    {
        return AcademicAlert::query()
            ->where('annee_universitaire_id', $yearId)
            ->where('semester', $period)
            ->when($classId, fn ($query) => $query->where('classe_id', $classId));
    }

    private function sheetQuery(?int $yearId, string $period, ?string $system = null, ?int $classId = null): Builder
    {
        return GradeSheet::query()
            ->where('annee_universitaire_id', $yearId)
            ->where('semester', $period)
            ->when($system, fn ($query) => $query->where('academic_system', $system))
            ->when($classId, fn ($query) => $query->where('classe_id', $classId));
    }

    private function freshness(?int $yearId, string $period, ?string $system, ?int $classId, ?Collection $classIds = null): array
    {
        $query = $this->scopeClasses($this->snapshotQuery($yearId, $period, $system, $classId), $classIds);
        $latest = (clone $query)->max('calculated_at');

        return [
            'last_updated_at' => $latest,
            'stale_count' => $query->where('is_dirty', true)->count(),
        ];
    }

    private function selectedYear(Request $request): ?ESBTPAnneeUniversitaire
    {
        if ($request->filled('year_id')) {
            return ESBTPAnneeUniversitaire::query()->find($request->integer('year_id'));
        }

        return ESBTPAnneeUniversitaire::query()
            ->where('is_current', true)
            ->orWhere('is_active', true)
            ->latest('annee_debut')
            ->first();
    }

    private function yearOptions(): Collection
    {
        return ESBTPAnneeUniversitaire::query()
            ->orderByDesc('annee_debut')
            ->limit(8)
            ->get(['id', 'name', 'annee_debut', 'annee_fin', 'start_date', 'end_date']);
    }

    private function classOptions(?int $yearId, ?Collection $classIds = null): Collection
    {
        return ESBTPClasse::query()
            ->without(['filiere', 'niveau', 'annee'])
            ->where('is_active', true)
            ->when($yearId, fn ($query) => $query->whereHas('inscriptions', fn ($inscriptions) => $inscriptions
                ->where('annee_universitaire_id', $yearId)
                ->where('status', 'active')))
            ->when($classIds !== null, fn ($query) => $query->whereIn('id', $classIds))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function period(Request $request): string
    {
        return (string) $request->input('period', 'semestre1');
    }

    private function system(Request $request): ?string
    {
        $system = strtoupper(trim((string) $request->input('system', '')));

        return in_array($system, ['BTS', 'LMD'], true) ? $system : null;
    }

    private function periodOptions(): array
    {
        return [
            'semestre1' => 'Semestre 1',
            'semestre2' => 'Semestre 2',
            'annuel' => 'Annuel',
        ];
    }

    private function snapshotPayload(?AcademicMetricSnapshot $snapshot): ?array
    {
        if ($snapshot === null) {
            return null;
        }

        return [
            'academic_score' => $snapshot->academic_score === null ? null : (float) $snapshot->academic_score,
            'operational_score' => $snapshot->operational_score === null ? null : (float) $snapshot->operational_score,
            'coverage_pct' => $snapshot->coverage_pct,
            'confidence_pct' => $snapshot->confidence_pct,
            'level' => $snapshot->level,
            'metrics' => $snapshot->metrics,
            'reasons' => $snapshot->reasons,
            'calculated_at' => optional($snapshot->calculated_at)->toIso8601String(),
            'is_dirty' => $snapshot->is_dirty,
        ];
    }

    private function syncSuccessMessage(array $stats): string
    {
        if (($stats['global_refresh'] ?? false) === true) {
            if (($stats['has_more'] ?? false) === true) {
                return 'Un premier lot a été recalculé. D’autres indicateurs obsolètes restent à traiter.';
            }

            return ($stats['snapshots_refreshed'] ?? 0) > 0
                ? 'Synchronisation terminée. Les indicateurs obsolètes ont été recalculés.'
                : 'La vue est déjà à jour. Aucun indicateur obsolète n’a été trouvé.';
        }

        if (($stats['classes_processed'] ?? 0) === 0) {
            return 'Aucune classe active ne correspond aux filtres sélectionnés.';
        }

        return 'Synchronisation terminée. Les indicateurs affichés ont été recalculés.';
    }

    private function classLabel(ESBTPClasse $classe): string
    {
        return trim(($classe->code ? "{$classe->code} · " : '').$classe->name);
    }

    private function scopeClasses(Builder $query, ?Collection $classIds): Builder
    {
        return $classIds === null ? $query : $query->whereIn('classe_id', $classIds);
    }
}
