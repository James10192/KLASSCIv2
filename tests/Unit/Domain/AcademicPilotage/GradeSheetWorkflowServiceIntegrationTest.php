<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\GradeSheetTransitionCommand;
use App\Domain\AcademicPilotage\Enums\GradeSheetAction;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Models\GradeSheetEvent;
use App\Domain\AcademicPilotage\Services\GradeSheetEventRecorder;
use App\Domain\AcademicPilotage\Services\GradeSheetStateMachine;
use App\Domain\AcademicPilotage\Services\GradeSheetWorkflowService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GradeSheetWorkflowServiceIntegrationTest extends AcademicPilotageDatabaseTestCase
{
    private GradeSheetWorkflowService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new GradeSheetWorkflowService(
            new GradeSheetStateMachine,
            new GradeSheetEventRecorder,
        );
    }

    public function test_transition_rejects_a_stale_lock_version_without_side_effects(): void
    {
        $sheet = $this->createGradeSheet(['lock_version' => 3]);

        try {
            $this->service->transition(new GradeSheetTransitionCommand(
                $sheet->id,
                GradeSheetAction::START_ENTRY,
                2,
                41,
            ));
            $this->fail('A stale transition should have been rejected.');
        } catch (AcademicPilotageException $exception) {
            $this->assertSame(AcademicPilotageException::STALE_GRADE_SHEET, $exception->errorCode);
            $this->assertSame(409, $exception->statusCode);
            $this->assertSame([
                'expected_lock_version' => 2,
                'actual_lock_version' => 3,
            ], $exception->details);
        }

        $sheet->refresh();
        $this->assertSame(GradeSheetStatus::EXPECTED, $sheet->status);
        $this->assertSame(3, $sheet->lock_version);
        $this->assertNull($sheet->entry_started_at);
        $this->assertSame(0, GradeSheetEvent::query()->count());
    }

    public function test_transition_updates_the_version_and_records_one_complete_event(): void
    {
        Carbon::setTestNow('2026-07-11 09:30:00');
        $sheet = $this->createGradeSheet();

        $transitioned = $this->service->transition(new GradeSheetTransitionCommand(
            $sheet->id,
            GradeSheetAction::START_ENTRY,
            1,
            42,
            null,
            ['request_id' => 'req-17'],
        ));

        $this->assertSame(GradeSheetStatus::IN_ENTRY, $transitioned->status);
        $this->assertSame(2, $transitioned->lock_version);
        $this->assertSame(42, $transitioned->updated_by);
        $this->assertTrue($transitioned->entry_started_at->equalTo(now()));

        $event = GradeSheetEvent::query()->sole();
        $this->assertSame($sheet->id, $event->grade_sheet_id);
        $this->assertSame(GradeSheetAction::START_ENTRY->value, $event->event_type);
        $this->assertSame(GradeSheetStatus::EXPECTED, $event->from_status);
        $this->assertSame(GradeSheetStatus::IN_ENTRY, $event->to_status);
        $this->assertSame(42, $event->actor_id);
        $this->assertNull($event->reason);
        $this->assertSame([
            'request_id' => 'req-17',
            'lock_version' => 2,
        ], $event->metadata);
        $this->assertTrue($event->occurred_at->equalTo(now()));
    }

    public function test_finish_entry_is_blocked_while_an_expected_entry_remains(): void
    {
        $sheet = $this->createGradeSheet([
            'status' => GradeSheetStatus::IN_ENTRY->value,
        ]);
        $this->createEntry($sheet, 101, [
            'status' => GradeSheetEntryStatus::ENTERED->value,
            'resolved_at' => now(),
        ]);
        $this->createEntry($sheet, 102, [
            'status' => GradeSheetEntryStatus::EXPECTED->value,
        ]);

        try {
            $this->service->transition(new GradeSheetTransitionCommand(
                $sheet->id,
                GradeSheetAction::FINISH_ENTRY,
                1,
                43,
            ));
            $this->fail('Finishing entry should require every expected entry to be resolved.');
        } catch (AcademicPilotageException $exception) {
            $this->assertSame(AcademicPilotageException::INCOMPLETE_ENTRIES, $exception->errorCode);
            $this->assertSame([
                'expected_entries' => 2,
                'unresolved_entries' => 1,
            ], $exception->details);
        }

        $sheet->refresh();
        $this->assertSame(GradeSheetStatus::IN_ENTRY, $sheet->status);
        $this->assertSame(1, $sheet->lock_version);
        $this->assertNull($sheet->entered_at);
        $this->assertSame(0, GradeSheetEvent::query()->count());
    }

    public function test_validation_rejects_a_note_changed_after_control(): void
    {
        $controlledAt = Carbon::parse('2026-07-11 09:30:00');
        $sheet = $this->createGradeSheet([
            'evaluation_id' => 77,
            'status' => GradeSheetStatus::CONTROLLED->value,
            'controlled_at' => $controlledAt,
        ]);
        $this->createEntry($sheet, 101, [
            'note_id' => 201,
            'status' => GradeSheetEntryStatus::ENTERED->value,
            'resolved_at' => $controlledAt,
        ]);
        DB::table('esbtp_notes')->insert([
            'id' => 201,
            'evaluation_id' => 77,
            'etudiant_id' => 101,
            'is_absent' => false,
            'created_by' => 42,
            'created_at' => $controlledAt,
            'updated_at' => $controlledAt->copy()->addMinute(),
        ]);

        try {
            $this->service->transition(new GradeSheetTransitionCommand(
                $sheet->id,
                GradeSheetAction::VALIDATE,
                1,
                43,
                'Validation finale',
            ));
            $this->fail('A note changed after control must block validation.');
        } catch (AcademicPilotageException $exception) {
            $this->assertSame(
                AcademicPilotageException::INVALID_ENTRY_EVIDENCE,
                $exception->errorCode,
            );
            $this->assertSame([
                'missing_notes' => 0,
                'stale_notes' => 1,
            ], $exception->details);
        }

        $this->assertSame(GradeSheetStatus::CONTROLLED, $sheet->fresh()->status);
        $this->assertSame(0, GradeSheetEvent::query()->count());
    }
}
