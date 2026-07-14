<?php

namespace App\Domain\AcademicPilotage\Presenters;

use App\Domain\AcademicPilotage\Enums\GradeSheetAction;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Services\GradeSheetStateMachine;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;

final class GradeSheetAllowedActionsPresenter
{
    public function __construct(
        private readonly GradeSheetStateMachine $stateMachine,
        private readonly Gate $gate,
    ) {}

    /** @return array<int, array{action: string, label: string, requires_reason: bool, authorization_ability: string, target_status: string}> */
    public function for(User $actor, GradeSheet $sheet): array
    {
        return array_values(array_filter(array_map(
            fn (GradeSheetAction $action): ?array => $this->metadataFor($actor, $sheet, $action),
            GradeSheetAction::cases(),
        )));
    }

    /** @return array{action: string, label: string, requires_reason: bool, authorization_ability: string, target_status: string}|null */
    private function metadataFor(User $actor, GradeSheet $sheet, GradeSheetAction $action): ?array
    {
        if (! $this->gate->forUser($actor)->allows($action->authorizationAbility(), $sheet)) {
            return null;
        }

        try {
            $plan = $this->stateMachine->transition(
                $sheet->status,
                $action,
                $sheet->entry_mode,
                $action->requiresReason() ? 'preview' : null,
            );
        } catch (AcademicPilotageException) {
            return null;
        }

        return [
            'action' => $action->value,
            'label' => $action->label(),
            'requires_reason' => $action->requiresReason(),
            'authorization_ability' => $action->authorizationAbility(),
            'target_status' => $plan->to->value,
        ];
    }
}
