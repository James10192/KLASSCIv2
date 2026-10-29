<?php

namespace App\Domain\AcademicPilotage\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicActorActivityService
{
    public function __construct(private readonly AcademicPeriodNormalizer $periods) {}

    public function summarize(
        ?int $yearId,
        string $period,
        ?string $system,
        ?int $classId,
        ?Collection $allowedClassIds,
        int $currentUserId,
        string $currentUserName,
    ): array {
        $actors = $this->activity(
            $yearId,
            $period,
            $system,
            $classId,
            $allowedClassIds,
        );

        return [
            'actors' => $actors->take(20)->all(),
            'current_actor' => $actors->firstWhere('id', $currentUserId)
                ?? $this->emptyActor($currentUserId, $currentUserName),
        ];
    }

    private function activity(
        ?int $yearId,
        string $period,
        ?string $system,
        ?int $classId,
        ?Collection $allowedClassIds,
    ): Collection {
        $activity = $this->noteActivityQuery('created_by', 'entered', 'created_at');
        if (Schema::hasColumn('esbtp_notes', 'updated_by')) {
            $activity->unionAll($this->noteActivityQuery('updated_by', 'updated', 'updated_at')
                ->whereNotNull('notes.updated_by')
                ->whereColumn('notes.updated_at', '>', 'notes.created_at'));
        }
        $activity->unionAll($this->sheetActivityQuery());

        return DB::query()
            ->fromSub($activity, 'activity')
            ->join('users', 'users.id', '=', 'activity.actor_id')
            ->tap(fn (Builder $query) => $this->applyScope(
                $query,
                $yearId,
                $period,
                $system,
                $classId,
                $allowedClassIds,
            ))
            ->selectRaw('users.id as user_id, users.name as actor_name')
            ->selectRaw("COUNT(DISTINCT CASE WHEN activity.action = 'entered' THEN activity.note_id END) as notes_entered")
            ->selectRaw("COUNT(DISTINCT CASE WHEN activity.action = 'updated' THEN activity.note_id END) as notes_updated")
            ->selectRaw('COUNT(DISTINCT activity.note_id) as notes_touched')
            ->selectRaw('COUNT(DISTINCT activity.sheet_id) as sheets_touched')
            ->selectRaw("COUNT(DISTINCT CASE WHEN activity.action = 'sheet_completed' THEN activity.sheet_id END) as sheets_completed")
            ->selectRaw('COUNT(DISTINCT activity.classe_id) as classes_count')
            ->selectRaw('COUNT(DISTINCT activity.matiere_id) as subjects_count')
            ->selectRaw('MAX(activity.activity_at) as last_activity_at')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('notes_entered')
            ->orderByDesc('notes_updated')
            ->orderByDesc('sheets_completed')
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->user_id,
                'name' => $row->actor_name,
                'notes_entered' => (int) $row->notes_entered,
                'notes_updated' => (int) $row->notes_updated,
                'notes_touched' => (int) $row->notes_touched,
                'sheets_touched' => (int) $row->sheets_touched,
                'sheets_completed' => (int) $row->sheets_completed,
                'classes_count' => (int) $row->classes_count,
                'subjects_count' => (int) $row->subjects_count,
                'last_activity_at' => $row->last_activity_at,
            ]);
    }

    private function noteActivityQuery(string $actorColumn, string $action, string $timestampColumn): Builder
    {
        return DB::table('esbtp_notes as notes')
            ->join('esbtp_evaluations as evaluations', 'evaluations.id', '=', 'notes.evaluation_id')
            ->leftJoin('esbtp_grade_sheets as sheets', function ($join): void {
                $join->on('sheets.evaluation_id', '=', 'evaluations.id')->whereNull('sheets.deleted_at');
            })
            ->leftJoin('esbtp_classes as classes', 'classes.id', '=', 'evaluations.classe_id')
            ->whereNotNull("notes.{$actorColumn}")
            ->whereNull('notes.deleted_at')
            ->whereNull('notes.archived_at')
            ->selectRaw("notes.{$actorColumn} as actor_id")
            ->selectRaw('notes.id as note_id')
            ->selectRaw("'{$action}' as action")
            ->selectRaw('evaluations.classe_id as classe_id')
            ->selectRaw('COALESCE(evaluations.matiere_id, sheets.matiere_id, notes.matiere_id) as matiere_id')
            ->selectRaw('sheets.id as sheet_id')
            ->selectRaw("notes.{$timestampColumn} as activity_at")
            ->selectRaw('COALESCE(evaluations.annee_universitaire_id, sheets.annee_universitaire_id) as year_id')
            ->selectRaw('COALESCE(evaluations.periode, sheets.semester) as period')
            ->selectRaw("COALESCE(sheets.academic_system, classes.systeme_academique, 'BTS') as academic_system");
    }

    private function sheetActivityQuery(): Builder
    {
        return DB::table('esbtp_grade_sheets as sheets')
            ->whereNotNull('sheets.entered_by')
            ->whereNull('sheets.deleted_at')
            ->selectRaw('sheets.entered_by as actor_id')
            ->selectRaw('NULL as note_id')
            ->selectRaw("'sheet_completed' as action")
            ->selectRaw('sheets.classe_id as classe_id')
            ->selectRaw('sheets.matiere_id as matiere_id')
            ->selectRaw('sheets.id as sheet_id')
            ->selectRaw('sheets.entered_at as activity_at')
            ->selectRaw('sheets.annee_universitaire_id as year_id')
            ->selectRaw('sheets.semester as period')
            ->selectRaw("COALESCE(sheets.academic_system, 'BTS') as academic_system");
    }

    private function applyScope(
        Builder $query,
        ?int $yearId,
        string $period,
        ?string $system,
        ?int $classId,
        ?Collection $allowedClassIds,
    ): void {
        $query
            ->where('activity.year_id', $yearId)
            ->when(
                $this->periods->normalize($period) !== 'annuel',
                fn (Builder $builder) => $builder->whereIn(
                    'activity.period',
                    $this->periods->databaseVariants($period),
                ),
            )
            ->when($system, fn (Builder $builder) => $builder->whereRaw(
                'UPPER(activity.academic_system) = ?',
                [strtoupper($system)],
            ))
            ->when($classId, fn (Builder $builder) => $builder->where('activity.classe_id', $classId))
            ->when($allowedClassIds !== null, fn (Builder $builder) => $builder->whereIn(
                'activity.classe_id',
                $allowedClassIds,
            ));
    }

    private function emptyActor(int $userId, string $userName): array
    {
        return [
            'id' => $userId,
            'name' => $userName,
            'notes_entered' => 0,
            'notes_updated' => 0,
            'notes_touched' => 0,
            'sheets_touched' => 0,
            'sheets_completed' => 0,
            'classes_count' => 0,
            'subjects_count' => 0,
            'last_activity_at' => null,
        ];
    }
}
