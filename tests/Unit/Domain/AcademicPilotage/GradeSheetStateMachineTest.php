<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetAction;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryMode;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Services\GradeSheetStateMachine;
use Tests\TestCase;

class GradeSheetStateMachineTest extends TestCase
{
    private GradeSheetStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateMachine = new GradeSheetStateMachine;
    }

    public function test_direct_entry_follows_the_expected_workflow_and_audit_fields(): void
    {
        $steps = [
            [GradeSheetStatus::EXPECTED, GradeSheetAction::START_ENTRY, GradeSheetStatus::IN_ENTRY, 'entry_started_at', null, null],
            [GradeSheetStatus::IN_ENTRY, GradeSheetAction::FINISH_ENTRY, GradeSheetStatus::ENTERED, 'entered_at', 'entered_by', null],
            [GradeSheetStatus::ENTERED, GradeSheetAction::CONTROL, GradeSheetStatus::CONTROLLED, 'controlled_at', 'controlled_by', null],
            [GradeSheetStatus::CONTROLLED, GradeSheetAction::VALIDATE, GradeSheetStatus::VALIDATED, 'validated_at', 'validated_by', 'Notes vérifiées'],
        ];

        foreach ($steps as [$from, $action, $to, $timestampField, $actorField, $reason]) {
            $plan = $this->stateMachine->transition($from, $action, GradeSheetEntryMode::DIRECT, $reason);

            $this->assertSame($from, $plan->from);
            $this->assertSame($to, $plan->to);
            $this->assertSame($action, $plan->action);
            $this->assertSame($timestampField, $plan->timestampField);
            $this->assertSame($actorField, $plan->actorField);
        }
    }

    public function test_paper_entry_follows_the_expected_workflow(): void
    {
        $steps = [
            [GradeSheetStatus::EXPECTED, GradeSheetAction::SUBMIT, GradeSheetStatus::SUBMITTED, 'submitted_at', 'submitted_by'],
            [GradeSheetStatus::SUBMITTED, GradeSheetAction::RECEIVE, GradeSheetStatus::RECEIVED, 'received_at', 'received_by'],
            [GradeSheetStatus::RECEIVED, GradeSheetAction::START_ENTRY, GradeSheetStatus::IN_ENTRY, 'entry_started_at', null],
        ];

        foreach ($steps as [$from, $action, $to, $timestampField, $actorField]) {
            $plan = $this->stateMachine->transition($from, $action, GradeSheetEntryMode::PAPER);

            $this->assertSame($to, $plan->to);
            $this->assertSame($timestampField, $plan->timestampField);
            $this->assertSame($actorField, $plan->actorField);
        }
    }

    public function test_correction_and_reopen_paths_depend_on_the_entry_mode(): void
    {
        foreach ([GradeSheetStatus::ENTERED, GradeSheetStatus::CONTROLLED] as $from) {
            $plan = $this->stateMachine->transition(
                $from,
                GradeSheetAction::REQUEST_CORRECTION,
                GradeSheetEntryMode::DIRECT,
                'Des notes sont incohérentes',
            );

            $this->assertSame(GradeSheetStatus::CORRECTION_REQUESTED, $plan->to);
            $this->assertNull($plan->timestampField);
            $this->assertNull($plan->actorField);
        }

        $direct = $this->stateMachine->transition(
            GradeSheetStatus::CORRECTION_REQUESTED,
            GradeSheetAction::REOPEN,
            GradeSheetEntryMode::DIRECT,
            'Correction autorisée',
        );
        $paper = $this->stateMachine->transition(
            GradeSheetStatus::CORRECTION_REQUESTED,
            GradeSheetAction::REOPEN,
            GradeSheetEntryMode::PAPER,
            'Nouvelle fiche attendue',
        );
        $validated = $this->stateMachine->transition(
            GradeSheetStatus::VALIDATED,
            GradeSheetAction::REOPEN,
            GradeSheetEntryMode::DIRECT,
            'Contrôle à reprendre',
        );

        $this->assertSame(GradeSheetStatus::IN_ENTRY, $direct->to);
        $this->assertSame(GradeSheetStatus::SUBMITTED, $paper->to);
        $this->assertSame(GradeSheetStatus::CONTROLLED, $validated->to);
    }

    public function test_reject_and_cancel_are_available_from_every_non_terminal_status(): void
    {
        foreach (GradeSheetStatus::cases() as $status) {
            if ($status->isTerminal()) {
                continue;
            }

            $rejected = $this->stateMachine->transition(
                $status,
                GradeSheetAction::REJECT,
                GradeSheetEntryMode::DIRECT,
                'Fiche non recevable',
            );
            $cancelled = $this->stateMachine->transition(
                $status,
                GradeSheetAction::CANCEL,
                GradeSheetEntryMode::DIRECT,
                'Évaluation annulée',
            );

            $this->assertSame(GradeSheetStatus::REJECTED, $rejected->to);
            $this->assertNull($rejected->timestampField);
            $this->assertNull($rejected->actorField);
            $this->assertSame(GradeSheetStatus::CANCELLED, $cancelled->to);
            $this->assertSame('cancelled_at', $cancelled->timestampField);
            $this->assertSame('cancelled_by', $cancelled->actorField);
        }
    }

    public function test_all_actions_requiring_a_reason_reject_blank_values(): void
    {
        $cases = [
            [GradeSheetStatus::CONTROLLED, GradeSheetAction::VALIDATE],
            [GradeSheetStatus::ENTERED, GradeSheetAction::REQUEST_CORRECTION],
            [GradeSheetStatus::CORRECTION_REQUESTED, GradeSheetAction::REOPEN],
            [GradeSheetStatus::EXPECTED, GradeSheetAction::REJECT],
            [GradeSheetStatus::EXPECTED, GradeSheetAction::CANCEL],
        ];

        foreach ($cases as [$from, $action]) {
            try {
                $this->stateMachine->transition($from, $action, GradeSheetEntryMode::DIRECT, '   ');
                $this->fail("L’action {$action->value} aurait dû exiger une raison.");
            } catch (AcademicPilotageException $exception) {
                $this->assertSame(AcademicPilotageException::REASON_REQUIRED, $exception->errorCode);
                $this->assertSame(422, $exception->statusCode);
                $this->assertSame(['action' => $action->value], $exception->details);
            }
        }
    }

    public function test_invalid_transition_has_a_stable_json_error_contract(): void
    {
        try {
            $this->stateMachine->transition(
                GradeSheetStatus::EXPECTED,
                GradeSheetAction::RECEIVE,
                GradeSheetEntryMode::DIRECT,
            );
            $this->fail('La transition aurait dû être refusée.');
        } catch (AcademicPilotageException $exception) {
            $response = $exception->render();

            $this->assertSame(422, $response->getStatusCode());
            $this->assertSame([
                'ok' => false,
                'error' => AcademicPilotageException::INVALID_TRANSITION,
                'message' => $exception->getMessage(),
                'details' => [
                    'from' => 'expected',
                    'action' => 'receive',
                    'entry_mode' => 'direct',
                ],
            ], $response->getData(true));
        }
    }

    public function test_terminal_statuses_reject_regular_transitions(): void
    {
        foreach ([GradeSheetStatus::VALIDATED, GradeSheetStatus::REJECTED, GradeSheetStatus::CANCELLED] as $status) {
            $action = $status === GradeSheetStatus::VALIDATED
                ? GradeSheetAction::CANCEL
                : GradeSheetAction::REOPEN;

            try {
                $this->stateMachine->transition(
                    $status,
                    $action,
                    GradeSheetEntryMode::DIRECT,
                    'Tentative interdite',
                );
                $this->fail("Le statut terminal {$status->value} aurait dû refuser la transition.");
            } catch (AcademicPilotageException $exception) {
                $this->assertSame(AcademicPilotageException::INVALID_TRANSITION, $exception->errorCode);
            }
        }
    }
}
