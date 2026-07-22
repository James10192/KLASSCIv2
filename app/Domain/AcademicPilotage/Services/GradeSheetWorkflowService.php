<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\GradeSheetTransitionCommand;
use App\Domain\AcademicPilotage\DTO\GradeSheetTransitionPlan;
use App\Domain\AcademicPilotage\Enums\GradeSheetAction;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetEntry;
use Illuminate\Support\Facades\DB;

final class GradeSheetWorkflowService
{
    private GradeSheetRevisionService $revisions;

    public function __construct(
        private readonly GradeSheetStateMachine $stateMachine,
        private readonly GradeSheetEventRecorder $eventRecorder,
        private readonly AcademicMetricSnapshotInvalidationService $invalidation,
        ?GradeSheetRevisionService $revisions = null,
    ) {
        $this->revisions = $revisions ?? new GradeSheetRevisionService;
    }

    public function transition(GradeSheetTransitionCommand $command): GradeSheet
    {
        $sheet = DB::transaction(function () use ($command): GradeSheet {
            $sheet = GradeSheet::query()->lockForUpdate()->findOrFail($command->gradeSheetId);
            $this->assertCurrentVersion($sheet, $command->expectedLockVersion);
            $plan = $this->stateMachine->transition(
                $sheet->status,
                $command->action,
                $sheet->entry_mode,
                $command->reason,
            );
            $this->assertEntriesComplete($sheet, $command->action);
            $newVersion = $command->expectedLockVersion + 1;
            $updates = $this->transitionUpdates($plan, $command, $newVersion);

            $affected = GradeSheet::query()
                ->whereKey($sheet->id)
                ->where('status', $plan->from->value)
                ->where('lock_version', $command->expectedLockVersion)
                ->update($updates);

            if ($affected !== 1) {
                $actualVersion = (int) GradeSheet::query()
                    ->whereKey($sheet->id)
                    ->value('lock_version');
                throw AcademicPilotageException::staleGradeSheet(
                    $command->expectedLockVersion,
                    $actualVersion,
                );
            }

            $this->eventRecorder->record(
                $sheet,
                $command->action->value,
                $plan->from,
                $plan->to,
                $command->actorId,
                $command->reason,
                [...$command->metadata, 'lock_version' => $newVersion],
            );

            if ($this->stateMachine->createsRevision($plan)) {
                $this->revisions->captureValidatedRevision($sheet->refresh(), $command->actorId, $command->reason);
            }

            return $sheet->refresh();
        });
        $this->invalidation->fromGradeSheet($sheet);

        return $sheet;
    }

    private function assertCurrentVersion(GradeSheet $sheet, int $expectedVersion): void
    {
        if ($sheet->lock_version !== $expectedVersion) {
            throw AcademicPilotageException::staleGradeSheet(
                $expectedVersion,
                $sheet->lock_version,
            );
        }
    }

    private function assertEntriesComplete(GradeSheet $sheet, GradeSheetAction $action): void
    {
        if (! in_array($action, [
            GradeSheetAction::FINISH_ENTRY,
            GradeSheetAction::CONTROL,
            GradeSheetAction::VALIDATE,
        ], true)) {
            return;
        }

        $entries = GradeSheetEntry::query()->where('grade_sheet_id', $sheet->id);
        $expectedCount = (clone $entries)->count();
        $unresolvedCount = (clone $entries)
            ->where('status', GradeSheetEntryStatus::EXPECTED->value)
            ->count();

        if ($expectedCount === 0 || $unresolvedCount > 0) {
            throw AcademicPilotageException::incompleteEntries(
                $expectedCount,
                $unresolvedCount,
            );
        }

        $gradeEntries = (clone $entries)->whereIn('status', [
            GradeSheetEntryStatus::ENTERED->value,
            GradeSheetEntryStatus::ABSENT->value,
        ]);
        $missingNotes = (clone $gradeEntries)
            ->where(function ($query): void {
                $query->whereNull('note_id')->orWhereDoesntHave('note');
            })
            ->count();
        $staleNotes = $action === GradeSheetAction::VALIDATE
            ? (clone $gradeEntries)
                ->whereHas('note', fn ($query) => $query->where(
                    'updated_at',
                    '>',
                    $sheet->controlled_at,
                ))
                ->count()
            : 0;

        if ($missingNotes > 0 || $staleNotes > 0) {
            throw AcademicPilotageException::invalidEntryEvidence(
                $missingNotes,
                $staleNotes,
            );
        }
    }

    private function transitionUpdates(
        GradeSheetTransitionPlan $plan,
        GradeSheetTransitionCommand $command,
        int $newVersion,
    ): array {
        $updates = [
            'status' => $plan->to->value,
            'lock_version' => $newVersion,
            'updated_by' => $command->actorId,
            'updated_at' => now(),
        ];

        if ($plan->timestampField !== null) {
            $updates[$plan->timestampField] = now();
        }

        if ($plan->actorField !== null) {
            $updates[$plan->actorField] = $command->actorId;
        }

        return $updates;
    }
}
