<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetRevision;
use App\Domain\AcademicPilotage\Services\GradeSheetRevisionService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

class GradeSheetRevisionServiceTest extends AcademicPilotageDatabaseTestCase
{
    private GradeSheetRevisionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::table('esbtp_evaluations', function (Blueprint $table): void {
            $table->softDeletes();
        });
        $migration = require database_path('migrations/2026_07_22_051713_create_esbtp_grade_sheet_revisions_table.php');
        $migration->up();
        $this->service = new GradeSheetRevisionService;
    }

    public function test_it_creates_a_deterministic_snapshot_and_retries_idempotently(): void
    {
        Carbon::setTestNow('2026-07-22 10:00:00');
        $sheet = $this->createValidatedSheet(4);
        $this->createEntry($sheet, 102, ['note_id' => 202, 'status' => 'entered']);
        $this->createEntry($sheet, 101, ['note_id' => 201, 'status' => 'absent']);
        $this->notes($sheet->evaluation_id, [[201, 101, true], [202, 102, false]]);

        $first = $this->service->captureValidatedRevision($sheet, 42, 'Validation finale');
        $retry = $this->service->captureValidatedRevision($sheet, 42, 'Validation finale');

        $this->assertSame($first->id, $retry->id);
        $this->assertSame(1, $first->revision_number);
        $this->assertSame(
            hash('sha256', json_encode($first->snapshot, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE)),
            $first->snapshot_sha256,
        );
        $this->assertSame([101, 102], array_column(array_column($first->snapshot['entries'], 'entry'), 'etudiant_id'));
        $this->assertImmutable($first);
    }

    public function test_revalidation_creates_a_linked_revision(): void
    {
        $sheet = $this->createValidatedSheet(2);
        $this->createEntry($sheet, 101, ['note_id' => 201, 'status' => 'entered']);
        $this->notes($sheet->evaluation_id, [[201, 101, false]]);
        $first = $this->service->captureValidatedRevision($sheet, 42, 'Validation initiale');

        DB::table('esbtp_grade_sheets')->where('id', $sheet->id)->update([
            'status' => 'validated',
            'lock_version' => 4,
            'validated_at' => now(),
        ]);
        $second = $this->service->captureValidatedRevision($sheet->fresh(), 43, 'Correction après réouverture');

        $this->assertSame(2, $second->revision_number);
        $this->assertSame($first->id, $second->parent_revision_id);
        $this->assertSame($second->id, $this->service->currentValidatedRevision($sheet)->id);
        $this->assertSame(2, GradeSheetRevision::query()->count());
    }

    public function test_it_rolls_back_when_revision_snapshot_persistence_fails(): void
    {
        DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY)');
        DB::statement('PRAGMA foreign_keys = ON');
        $sheet = $this->createValidatedSheet(3);

        $this->expectException(QueryException::class);
        try {
            $this->service->captureValidatedRevision($sheet, 999, 'Acteur inexistant');
        } finally {
            $this->assertSame(0, GradeSheetRevision::query()->count());
        }
    }

    public function test_it_refuses_a_stale_sheet_lock_version(): void
    {
        $sheet = $this->createValidatedSheet(3);
        DB::table('esbtp_grade_sheets')->where('id', $sheet->id)->update(['lock_version' => 4]);

        $this->expectException(AcademicPilotageException::class);
        try {
            $this->service->captureValidatedRevision($sheet, 42, 'Version obsolète');
        } finally {
            $this->assertSame(0, GradeSheetRevision::query()->count());
        }
    }

    private function createValidatedSheet(int $lockVersion): GradeSheet
    {
        $evaluationId = DB::table('esbtp_evaluations')->insertGetId([
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'matiere_id' => 30,
        ]);

        return $this->createGradeSheet([
            'evaluation_id' => $evaluationId,
            'matiere_id' => 30,
            'status' => 'validated',
            'lock_version' => $lockVersion,
            'validated_at' => now(),
        ]);
    }

    private function assertImmutable(GradeSheetRevision $revision): void
    {
        try {
            $revision->update(['reason' => 'Interdit']);
            $this->fail('Une révision ne doit pas être modifiable.');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }

        try {
            $revision->delete();
            $this->fail('Une révision ne doit pas être supprimable.');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }
    }

    private function notes(int $evaluationId, array $notes): void
    {
        foreach ($notes as [$id, $studentId, $absent]) {
            DB::table('esbtp_notes')->insert([
                'id' => $id,
                'evaluation_id' => $evaluationId,
                'etudiant_id' => $studentId,
                'is_absent' => $absent,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
