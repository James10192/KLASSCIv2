<?php

namespace App\Http\Controllers\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\AcademicAlertStatus;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\AcademicAlert;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Services\AcademicPilotageManualSyncService;
use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AcademicPilotageController extends Controller
{
    public function index(Request $request): View
    {
        $year = $this->selectedYear($request);
        $classes = $this->classOptions($year?->id);

        return view('esbtp.pilotage-academique.index', [
            'annees' => $this->yearOptions(),
            'selectedYear' => $year,
            'classes' => $classes,
            'periods' => $this->periodOptions(),
            'systems' => ['' => 'Tous les systèmes', 'BTS' => 'BTS', 'LMD' => 'LMD'],
            'initialFilters' => [
                'year_id' => $year?->id,
                'period' => $request->input('period', 'semestre1'),
                'system' => $request->input('system', ''),
                'class_id' => $request->input('class_id', ''),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $year = $this->selectedYear($request);
        $period = $this->period($request);
        $system = $this->system($request);
        $classId = $request->integer('class_id') ?: null;

        return response()->json([
            'ok' => true,
            'filters' => [
                'year_id' => $year?->id,
                'period' => $period,
                'system' => $system,
                'class_id' => $classId,
            ],
            'summary' => $this->summary($year?->id, $period, $system, $classId),
            'classes' => $this->classes($year?->id, $period, $system, $classId),
            'alerts' => $this->alerts($year?->id, $period, $classId),
            'sheets' => $this->sheets($year?->id, $period, $system, $classId),
            'students' => $this->students($year?->id, $period, $system, $classId),
            'freshness' => $this->freshness($year?->id, $period, $system, $classId),
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
        if ($classId === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Choisissez une classe avant de synchroniser. La synchronisation manuelle est ciblée pour éviter un recalcul global trop long.',
            ], 422);
        }

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
        $snapshot = $this->snapshotQuery($year?->id, $period, null, (int) $classe->id)
            ->where('scope_type', 'class')
            ->latest('calculated_at')
            ->first();

        return response()->json([
            'ok' => true,
            'classe' => $this->classLabel($classe),
            'health' => $this->snapshotPayload($snapshot),
            'alerts' => $this->alerts($year?->id, $period, (int) $classe->id, 8),
            'sheets' => $this->sheets($year?->id, $period, null, (int) $classe->id, 8),
            'students' => $this->students($year?->id, $period, null, (int) $classe->id, 8),
        ]);
    }

    public function studentHealth(Request $request, ESBTPEtudiant $etudiant): JsonResponse
    {
        $year = $this->selectedYear($request);
        $period = $this->period($request);
        $snapshot = $this->snapshotQuery($year?->id, $period)
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
            'alerts' => $this->alerts($year?->id, $period, null, 8, (int) $etudiant->id),
        ]);
    }

    private function summary(?int $yearId, string $period, ?string $system, ?int $classId): array
    {
        $snapshots = $this->snapshotQuery($yearId, $period, $system, $classId);
        $alerts = $this->alertQuery($yearId, $period, $classId);
        $sheets = $this->sheetQuery($yearId, $period, $system, $classId);

        $academicScore = (clone $snapshots)->where('scope_type', 'class')->avg('academic_score');
        $operationalScore = (clone $snapshots)->where('scope_type', 'class')->avg('operational_score');

        return [
            'academic_score' => $academicScore === null ? null : round((float) $academicScore, 2),
            'operational_score' => $operationalScore === null ? null : round((float) $operationalScore, 2),
            'open_alerts' => (clone $alerts)->whereIn('status', [
                AcademicAlertStatus::OPEN->value,
                AcademicAlertStatus::ACKNOWLEDGED->value,
                AcademicAlertStatus::IN_PROGRESS->value,
            ])->count(),
            'blocking_alerts' => (clone $alerts)->where('severity', 'blocking')->count(),
            'sheets_pending' => (clone $sheets)->whereNotIn('status', [
                GradeSheetStatus::VALIDATED->value,
                GradeSheetStatus::CANCELLED->value,
            ])->count(),
            'bulletin_blockers' => (clone $alerts)->where('type', 'bulletin_blocked')->count(),
        ];
    }

    private function classes(?int $yearId, string $period, ?string $system, ?int $classId, int $limit = 12): array
    {
        return $this->snapshotQuery($yearId, $period, $system, $classId)
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

    private function alerts(?int $yearId, string $period, ?int $classId = null, int $limit = 10, ?int $studentId = null): array
    {
        return $this->alertQuery($yearId, $period, $classId)
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

    private function sheets(?int $yearId, string $period, ?string $system, ?int $classId, int $limit = 10): array
    {
        return $this->sheetQuery($yearId, $period, $system, $classId)
            ->with(['classe:id,name,code', 'matiere:id,name,code', 'teacher:id,name'])
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (GradeSheet $sheet): array => [
                'id' => $sheet->id,
                'code' => $sheet->code,
                'status' => $sheet->status->value,
                'status_label' => $sheet->status->label(),
                'entry_mode' => $sheet->entry_mode->value,
                'classe' => $sheet->classe ? $this->classLabel($sheet->classe) : null,
                'matiere' => $sheet->matiere?->name ?? $sheet->matiere?->code,
                'teacher' => $sheet->teacher?->name,
                'expected_at' => optional($sheet->expected_at)->toDateString(),
                'updated_at' => optional($sheet->updated_at)->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function students(?int $yearId, string $period, ?string $system, ?int $classId, int $limit = 10): array
    {
        return $this->snapshotQuery($yearId, $period, $system, $classId)
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
            ->when($yearId, fn ($query) => $query->where('annee_universitaire_id', $yearId))
            ->where('semester', $period)
            ->when($system, fn ($query) => $query->where('academic_system', $system))
            ->when($classId, fn ($query) => $query->where('classe_id', $classId));
    }

    private function alertQuery(?int $yearId, string $period, ?int $classId = null): Builder
    {
        return AcademicAlert::query()
            ->when($yearId, fn ($query) => $query->where('annee_universitaire_id', $yearId))
            ->where('semester', $period)
            ->when($classId, fn ($query) => $query->where('classe_id', $classId));
    }

    private function sheetQuery(?int $yearId, string $period, ?string $system = null, ?int $classId = null): Builder
    {
        return GradeSheet::query()
            ->when($yearId, fn ($query) => $query->where('annee_universitaire_id', $yearId))
            ->where('semester', $period)
            ->when($system, fn ($query) => $query->where('academic_system', $system))
            ->when($classId, fn ($query) => $query->where('classe_id', $classId));
    }

    private function freshness(?int $yearId, string $period, ?string $system, ?int $classId): array
    {
        $latest = $this->snapshotQuery($yearId, $period, $system, $classId)->max('calculated_at');

        return [
            'last_updated_at' => $latest,
            'stale_count' => $this->snapshotQuery($yearId, $period, $system, $classId)->where('is_dirty', true)->count(),
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
            ->get(['id', 'name', 'annee_debut']);
    }

    private function classOptions(?int $yearId): Collection
    {
        return ESBTPClasse::query()
            ->without(['filiere', 'niveau', 'annee'])
            ->where('is_active', true)
            ->when($yearId, fn ($query) => $query->where('annee_universitaire_id', $yearId))
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
        if (($stats['classes_processed'] ?? 0) === 0) {
            return 'Aucune classe active ne correspond aux filtres sélectionnés.';
        }

        return 'Synchronisation terminée. Les indicateurs affichés ont été recalculés.';
    }

    private function classLabel(ESBTPClasse $classe): string
    {
        return trim(($classe->code ? "{$classe->code} · " : '').$classe->name);
    }
}
