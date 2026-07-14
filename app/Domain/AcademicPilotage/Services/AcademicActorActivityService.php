<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AcademicActorActivityService
{
    public function summarize(
        ?int $yearId,
        string $period,
        ?string $system,
        ?int $classId,
        ?Collection $allowedClassIds,
        int $currentUserId,
        string $currentUserName,
    ): array {
        $entryActivity = $this->entryActivity(
            $yearId,
            $period,
            $system,
            $classId,
            $allowedClassIds,
        );
        $sheetActivity = $this->sheetActivity(
            $yearId,
            $period,
            $system,
            $classId,
            $allowedClassIds,
        );

        $actors = $entryActivity->keys()
            ->merge($sheetActivity->keys())
            ->unique()
            ->map(fn (int $userId): array => $this->actorPayload($userId, [
                $entryActivity->get($userId, []),
                $sheetActivity->get($userId, []),
            ]))
            ->sortByDesc(fn (array $actor): array => [
                $actor['notes_entered'],
                $actor['sheets_completed'],
                $actor['last_activity_at'] ?? '',
            ])
            ->values();

        return [
            'actors' => $actors->take(20)->all(),
            'current_actor' => $actors->firstWhere('id', $currentUserId)
                ?? $this->emptyActor($currentUserId, $currentUserName),
        ];
    }

    private function entryActivity(
        ?int $yearId,
        string $period,
        ?string $system,
        ?int $classId,
        ?Collection $allowedClassIds,
    ): Collection {
        return GradeSheetEntry::query()
            ->join('esbtp_grade_sheets as sheets', 'sheets.id', '=', 'esbtp_grade_sheet_entries.grade_sheet_id')
            ->join('users', 'users.id', '=', 'esbtp_grade_sheet_entries.entered_by')
            ->whereNotNull('esbtp_grade_sheet_entries.entered_by')
            ->whereNull('sheets.deleted_at')
            ->tap(fn (Builder $query) => $this->applyScope(
                $query,
                $yearId,
                $period,
                $system,
                $classId,
                $allowedClassIds,
            ))
            ->selectRaw('users.id as user_id, users.name as actor_name')
            ->selectRaw('COUNT(DISTINCT esbtp_grade_sheet_entries.id) as notes_entered')
            ->selectRaw('COUNT(DISTINCT sheets.id) as sheets_touched')
            ->selectRaw('COUNT(DISTINCT sheets.classe_id) as classes_count')
            ->selectRaw('COUNT(DISTINCT sheets.matiere_id) as subjects_count')
            ->selectRaw('MAX(esbtp_grade_sheet_entries.updated_at) as last_activity_at')
            ->groupBy('users.id', 'users.name')
            ->get()
            ->keyBy('user_id')
            ->map(fn ($row): array => [
                'name' => $row->actor_name,
                'notes_entered' => (int) $row->notes_entered,
                'sheets_touched' => (int) $row->sheets_touched,
                'classes_count' => (int) $row->classes_count,
                'subjects_count' => (int) $row->subjects_count,
                'last_activity_at' => $row->last_activity_at,
            ]);
    }

    private function sheetActivity(
        ?int $yearId,
        string $period,
        ?string $system,
        ?int $classId,
        ?Collection $allowedClassIds,
    ): Collection {
        return GradeSheet::query()
            ->join('users', 'users.id', '=', 'esbtp_grade_sheets.entered_by')
            ->whereNotNull('esbtp_grade_sheets.entered_by')
            ->tap(fn (Builder $query) => $this->applyScope(
                $query,
                $yearId,
                $period,
                $system,
                $classId,
                $allowedClassIds,
            ))
            ->selectRaw('users.id as user_id, users.name as actor_name')
            ->selectRaw('COUNT(DISTINCT esbtp_grade_sheets.id) as sheets_completed')
            ->selectRaw('MAX(esbtp_grade_sheets.entered_at) as last_activity_at')
            ->groupBy('users.id', 'users.name')
            ->get()
            ->keyBy('user_id')
            ->map(fn ($row): array => [
                'name' => $row->actor_name,
                'sheets_completed' => (int) $row->sheets_completed,
                'last_activity_at' => $row->last_activity_at,
            ]);
    }

    private function applyScope(
        Builder $query,
        ?int $yearId,
        string $period,
        ?string $system,
        ?int $classId,
        ?Collection $allowedClassIds,
    ): void {
        $table = $query->getModel()->getTable() === 'esbtp_grade_sheet_entries'
            ? 'sheets'
            : 'esbtp_grade_sheets';

        $query
            ->where("{$table}.annee_universitaire_id", $yearId)
            ->where("{$table}.semester", $period)
            ->when($system, fn (Builder $builder) => $builder->where("{$table}.academic_system", $system))
            ->when($classId, fn (Builder $builder) => $builder->where("{$table}.classe_id", $classId))
            ->when($allowedClassIds !== null, fn (Builder $builder) => $builder->whereIn("{$table}.classe_id", $allowedClassIds));
    }

    private function actorPayload(int $userId, array $records): array
    {
        $merged = collect($records)->collapse();

        return [
            'id' => $userId,
            'name' => $merged->get('name', 'Utilisateur supprimé'),
            'notes_entered' => (int) $merged->get('notes_entered', 0),
            'sheets_touched' => (int) $merged->get('sheets_touched', 0),
            'sheets_completed' => (int) $merged->get('sheets_completed', 0),
            'classes_count' => (int) $merged->get('classes_count', 0),
            'subjects_count' => (int) $merged->get('subjects_count', 0),
            'last_activity_at' => collect($records)->pluck('last_activity_at')->filter()->max(),
        ];
    }

    private function emptyActor(int $userId, string $userName): array
    {
        return [
            'id' => $userId,
            'name' => $userName,
            'notes_entered' => 0,
            'sheets_touched' => 0,
            'sheets_completed' => 0,
            'classes_count' => 0,
            'subjects_count' => 0,
            'last_activity_at' => null,
        ];
    }
}
