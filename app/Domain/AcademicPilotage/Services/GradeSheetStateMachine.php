<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\GradeSheetTransitionPlan;
use App\Domain\AcademicPilotage\Enums\GradeSheetAction;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryMode;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;

final class GradeSheetStateMachine
{
    public function transition(
        GradeSheetStatus $from,
        GradeSheetAction $action,
        GradeSheetEntryMode $mode,
        ?string $reason = null,
    ): GradeSheetTransitionPlan {
        $to = $this->resolveTarget($from, $action, $mode);

        if ($to === null) {
            throw AcademicPilotageException::invalidTransition($from, $action, $mode);
        }

        $this->ensureReasonIsPresent($action, $reason);

        [$timestampField, $actorField] = $this->auditFields($action);

        return new GradeSheetTransitionPlan(
            $from,
            $to,
            $action,
            $timestampField,
            $actorField,
        );
    }

    private function ensureReasonIsPresent(GradeSheetAction $action, ?string $reason): void
    {
        if ($action->requiresReason() && ($reason === null || trim($reason) === '')) {
            throw AcademicPilotageException::reasonRequired($action);
        }
    }

    private function resolveTarget(
        GradeSheetStatus $from,
        GradeSheetAction $action,
        GradeSheetEntryMode $mode,
    ): ?GradeSheetStatus {
        if (! $from->isTerminal()) {
            if ($action === GradeSheetAction::REJECT) {
                return GradeSheetStatus::REJECTED;
            }

            if ($action === GradeSheetAction::CANCEL) {
                return GradeSheetStatus::CANCELLED;
            }
        }

        return match ([$from, $action]) {
            [GradeSheetStatus::EXPECTED, GradeSheetAction::START_ENTRY] => $mode === GradeSheetEntryMode::DIRECT ? GradeSheetStatus::IN_ENTRY : null,
            [GradeSheetStatus::EXPECTED, GradeSheetAction::SUBMIT] => $mode === GradeSheetEntryMode::PAPER ? GradeSheetStatus::SUBMITTED : null,
            [GradeSheetStatus::SUBMITTED, GradeSheetAction::RECEIVE] => $mode === GradeSheetEntryMode::PAPER ? GradeSheetStatus::RECEIVED : null,
            [GradeSheetStatus::RECEIVED, GradeSheetAction::START_ENTRY] => $mode === GradeSheetEntryMode::PAPER ? GradeSheetStatus::IN_ENTRY : null,
            [GradeSheetStatus::IN_ENTRY, GradeSheetAction::FINISH_ENTRY] => GradeSheetStatus::ENTERED,
            [GradeSheetStatus::ENTERED, GradeSheetAction::CONTROL] => GradeSheetStatus::CONTROLLED,
            [GradeSheetStatus::CONTROLLED, GradeSheetAction::VALIDATE] => GradeSheetStatus::VALIDATED,
            [GradeSheetStatus::ENTERED, GradeSheetAction::REQUEST_CORRECTION],
            [GradeSheetStatus::CONTROLLED, GradeSheetAction::REQUEST_CORRECTION] => GradeSheetStatus::CORRECTION_REQUESTED,
            [GradeSheetStatus::CORRECTION_REQUESTED, GradeSheetAction::REOPEN] => $mode === GradeSheetEntryMode::PAPER ? GradeSheetStatus::SUBMITTED : GradeSheetStatus::IN_ENTRY,
            [GradeSheetStatus::VALIDATED, GradeSheetAction::REOPEN] => GradeSheetStatus::CONTROLLED,
            default => null,
        };
    }

    /** @return array{0: ?string, 1: ?string} */
    private function auditFields(GradeSheetAction $action): array
    {
        return match ($action) {
            GradeSheetAction::SUBMIT => ['submitted_at', 'submitted_by'],
            GradeSheetAction::RECEIVE => ['received_at', 'received_by'],
            GradeSheetAction::START_ENTRY => ['entry_started_at', null],
            GradeSheetAction::FINISH_ENTRY => ['entered_at', 'entered_by'],
            GradeSheetAction::CONTROL => ['controlled_at', 'controlled_by'],
            GradeSheetAction::VALIDATE => ['validated_at', 'validated_by'],
            GradeSheetAction::CANCEL => ['cancelled_at', 'cancelled_by'],
            default => [null, null],
        };
    }
}
