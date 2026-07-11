<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Models\GradeSheetEntry;
use App\Domain\AcademicPilotage\Models\GradeSheetEvent;
use App\Domain\AcademicPilotage\Services\ExpectedGradeSheetEntrySynchronizer;
use App\Domain\AcademicPilotage\Services\GradeSheetEventRecorder;
use Illuminate\Support\Facades\DB;

class ExpectedGradeSheetEntrySynchronizerIntegrationTest extends AcademicPilotageDatabaseTestCase
{
    private ExpectedGradeSheetEntrySynchronizer $synchronizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->synchronizer = new ExpectedGradeSheetEntrySynchronizer(
            new GradeSheetEventRecorder,
        );
    }

    public function test_sync_rejects_a_stale_lock_version_before_mutating_entries(): void
    {
        $sheet = $this->createGradeSheet([
            'evaluation_id' => 77,
            'lock_version' => 2,
        ]);
        $this->insertInscription(101);

        try {
            $this->synchronizer->sync($sheet, 1, $this->actor(50));
            $this->fail('A stale synchronization should have been rejected.');
        } catch (AcademicPilotageException $exception) {
            $this->assertSame(AcademicPilotageException::STALE_GRADE_SHEET, $exception->errorCode);
            $this->assertSame([
                'expected_lock_version' => 1,
                'actual_lock_version' => 2,
            ], $exception->details);
        }

        $this->assertSame(0, GradeSheetEntry::query()->count());
        $this->assertSame(0, GradeSheetEvent::query()->count());
        $this->assertSame(2, $sheet->fresh()->lock_version);
    }

    public function test_sync_uses_only_the_active_completed_cohort_and_resolves_existing_notes(): void
    {
        $sheet = $this->createGradeSheet([
            'evaluation_id' => 77,
            'lock_version' => 4,
        ]);

        $this->insertInscription(101);
        $this->insertInscription(101);
        $this->insertInscription(102);
        $this->insertInscription(103);
        $this->insertInscription(104, ['status' => 'inactive']);
        $this->insertInscription(105, ['workflow_step' => 'documents_complets']);
        $this->insertInscription(106, ['classe_id' => 999]);
        $this->insertInscription(107, ['annee_universitaire_id' => 999]);

        $this->insertNote(201, 101, false, 61);
        $this->insertNote(202, 102, true, 62);
        $this->insertNote(203, 104, false, 63);
        $this->insertNote(204, 103, false, 64, ['evaluation_id' => 999]);

        $this->createEntry($sheet, 103, [
            'status' => GradeSheetEntryStatus::EXEMPT->value,
            'source' => 'manual',
            'resolved_at' => now(),
            'metadata' => json_encode(['decision' => 'jury']),
        ]);

        $result = $this->synchronizer->sync($sheet, 4, $this->actor(50));

        $this->assertSame([
            'created' => 2,
            'updated' => 1,
            'deactivated' => 0,
            'unchanged' => 0,
            'lock_version' => 5,
        ], $result->toArray());

        $entries = GradeSheetEntry::query()->orderBy('etudiant_id')->get()->keyBy('etudiant_id');
        $this->assertSame([101, 102, 103], $entries->keys()->all());
        $this->assertSame(GradeSheetEntryStatus::ENTERED, $entries[101]->status);
        $this->assertSame(201, $entries[101]->note_id);
        $this->assertSame(61, $entries[101]->entered_by);
        $this->assertSame(GradeSheetEntryStatus::ABSENT, $entries[102]->status);
        $this->assertSame(202, $entries[102]->note_id);
        $this->assertSame(GradeSheetEntryStatus::EXEMPT, $entries[103]->status);
        $this->assertNull($entries[103]->note_id);
        $this->assertSame([
            'decision' => 'jury',
            'cohort_active' => true,
        ], $entries[103]->metadata);

        $this->assertSame(5, $sheet->fresh()->lock_version);
        $event = GradeSheetEvent::query()->sole();
        $this->assertSame('entries_synced', $event->event_type);
        $this->assertSame(GradeSheetStatus::EXPECTED, $event->from_status);
        $this->assertSame(GradeSheetStatus::EXPECTED, $event->to_status);
        $this->assertSame(50, $event->actor_id);
        $this->assertSame([
            'created' => 2,
            'updated' => 1,
            'unchanged' => 0,
            'deactivated' => 0,
            'lock_version' => 5,
        ], $event->metadata);
    }

    public function test_missing_student_becomes_not_applicable_without_deletion_and_remains_idempotent(): void
    {
        $sheet = $this->createGradeSheet(['evaluation_id' => 77]);
        $this->insertInscription(101);
        $this->createEntry($sheet, 101, [
            'source' => 'cohort_sync',
            'metadata' => json_encode(['cohort_active' => true]),
        ]);
        $removedEntryId = $this->createEntry($sheet, 109, [
            'note_id' => 209,
            'status' => GradeSheetEntryStatus::ENTERED->value,
            'source' => 'cohort_sync',
            'entered_by' => 65,
            'resolved_at' => now(),
            'metadata' => json_encode(['origin' => 'initial_sync']),
        ]);

        $first = $this->synchronizer->sync($sheet, 1, $this->actor(50));

        $this->assertSame(1, $first->deactivated);
        $this->assertSame(2, GradeSheetEntry::query()->count());
        $removed = GradeSheetEntry::query()->findOrFail($removedEntryId);
        $this->assertSame(GradeSheetEntryStatus::NOT_APPLICABLE, $removed->status);
        $this->assertSame(209, $removed->note_id);
        $this->assertNotNull($removed->resolved_at);
        $this->assertSame([
            'origin' => 'initial_sync',
            'cohort_active' => false,
            'previous_status' => GradeSheetEntryStatus::ENTERED->value,
        ], $removed->metadata);

        $second = $this->synchronizer->sync($sheet->fresh(), 2, $this->actor(50));

        $this->assertSame(0, $second->deactivated);
        $this->assertSame(1, $second->unchanged);
        $this->assertSame(3, $second->lockVersion);
        $this->assertSame(2, GradeSheetEntry::query()->count());
        $removed->refresh();
        $this->assertSame($removedEntryId, $removed->id);
        $this->assertSame(GradeSheetEntryStatus::NOT_APPLICABLE, $removed->status);
        $this->assertSame(GradeSheetEntryStatus::ENTERED->value, $removed->metadata['previous_status']);
    }

    public function test_duplicate_active_notes_are_rejected_without_partial_sync(): void
    {
        $sheet = $this->createGradeSheet(['evaluation_id' => 77]);
        $this->insertInscription(101);
        $this->insertNote(201, 101, false, 61);
        $this->insertNote(202, 101, false, 62);

        try {
            $this->synchronizer->sync($sheet, 1, $this->actor(50));
            $this->fail('Duplicate notes must be reported explicitly.');
        } catch (AcademicPilotageException $exception) {
            $this->assertSame(AcademicPilotageException::DUPLICATE_NOTES, $exception->errorCode);
            $this->assertSame(['student_ids' => [101]], $exception->details);
        }

        $this->assertSame(0, GradeSheetEntry::query()->count());
        $this->assertSame(1, $sheet->fresh()->lock_version);
        $this->assertSame(0, GradeSheetEvent::query()->count());
    }

    public function test_controlled_sheet_cannot_be_resynchronized(): void
    {
        $sheet = $this->createGradeSheet([
            'status' => GradeSheetStatus::CONTROLLED->value,
        ]);

        $this->expectException(AcademicPilotageException::class);
        $this->expectExceptionMessage('synchroniser les entrées');

        $this->synchronizer->sync($sheet, 1, $this->actor(50));
    }

    private function insertInscription(int $studentId, array $attributes = []): void
    {
        DB::table('esbtp_inscriptions')->insert(array_merge([
            'etudiant_id' => $studentId,
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function insertNote(
        int $id,
        int $studentId,
        bool $absent,
        int $creatorId,
        array $attributes = []
    ): void {
        DB::table('esbtp_notes')->insert(array_merge([
            'id' => $id,
            'evaluation_id' => 77,
            'etudiant_id' => $studentId,
            'is_absent' => $absent,
            'created_by' => $creatorId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
