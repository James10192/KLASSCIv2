<?php

namespace App\Domain\AcademicPilotage\Presenters;

use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetEntry;
use App\Models\User;
use Illuminate\Support\Collection;

final class GradeSheetSummaryPresenter
{
    public function __construct(
        private readonly GradeSheetAllowedActionsPresenter $allowedActions,
    ) {}

    public function collection(Collection $sheets, User $actor): array
    {
        $entryActors = $this->entryActorsBySheet($sheets->pluck('id'));

        return $sheets
            ->map(fn (GradeSheet $sheet): array => $this->sheet($sheet, $entryActors->get($sheet->id, []), $actor))
            ->values()
            ->all();
    }

    private function sheet(GradeSheet $sheet, array $entryActors, User $actor): array
    {
        $latestEvent = $sheet->latestEvent;

        return [
            'id' => $sheet->id,
            'code' => $sheet->code,
            'status' => $sheet->status->value,
            'status_label' => $sheet->status->label(),
            'entry_mode' => $sheet->entry_mode->value,
            'lock_version' => $sheet->lock_version,
            'allowed_actions' => $this->allowedActions->for($actor, $sheet),
            'classe' => $sheet->classe ? $this->classLabel($sheet) : null,
            'matiere' => $sheet->matiere?->name ?? $sheet->matiere?->code,
            'teacher' => $sheet->teacher?->name,
            'assigned_processor' => $this->userLabel($sheet->assignedProcessor),
            'submitted_by' => $this->userLabel($sheet->submittedBy),
            'received_by' => $this->userLabel($sheet->receivedBy),
            'entered_by' => $this->userLabel($sheet->enteredBy),
            'controlled_by' => $this->userLabel($sheet->controlledBy),
            'validated_by' => $this->userLabel($sheet->validatedBy),
            'entry_actors' => $entryActors,
            'expected_at' => optional($sheet->expected_at)->toDateString(),
            'submitted_at' => optional($sheet->submitted_at)->toIso8601String(),
            'received_at' => optional($sheet->received_at)->toIso8601String(),
            'entered_at' => optional($sheet->entered_at)->toIso8601String(),
            'controlled_at' => optional($sheet->controlled_at)->toIso8601String(),
            'validated_at' => optional($sheet->validated_at)->toIso8601String(),
            'updated_at' => optional($sheet->updated_at)->toIso8601String(),
            'entries_count' => (int) $sheet->entries_count,
            'entered_entries_count' => (int) $sheet->entered_entries_count,
            'resolved_entries_count' => (int) $sheet->resolved_entries_count,
            'latest_event' => $latestEvent ? [
                'type' => $latestEvent->event_type,
                'actor' => $this->userLabel($latestEvent->actor),
                'occurred_at' => optional($latestEvent->occurred_at)->toIso8601String(),
                'reason' => $latestEvent->reason,
            ] : null,
        ];
    }

    private function entryActorsBySheet(Collection $sheetIds): Collection
    {
        if ($sheetIds->isEmpty()) {
            return collect();
        }

        return GradeSheetEntry::query()
            ->whereIn('grade_sheet_id', $sheetIds)
            ->whereNotNull('entered_by')
            ->with('enteredBy:id,name,email')
            ->get(['grade_sheet_id', 'entered_by'])
            ->groupBy('grade_sheet_id')
            ->map(fn (Collection $entries): array => $entries
                ->map(fn (GradeSheetEntry $entry): ?string => $this->userLabel($entry->enteredBy))
                ->filter()
                ->unique()
                ->values()
                ->all());
    }

    private function classLabel(GradeSheet $sheet): string
    {
        return trim(($sheet->classe->code ? $sheet->classe->code.' · ' : '').$sheet->classe->name);
    }

    private function userLabel(?User $user): ?string
    {
        return $user?->name ?: $user?->email;
    }
}
