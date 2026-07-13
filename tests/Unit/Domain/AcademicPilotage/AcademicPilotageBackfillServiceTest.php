<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetEntry;
use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Domain\AcademicPilotage\Services\AcademicPilotageBackfillService;
use App\Domain\AcademicPilotage\Services\ExpectedGradeSheetEntrySynchronizer;
use App\Domain\AcademicPilotage\Services\GradeSheetEventRecorder;
use App\Domain\AcademicPilotage\Services\GradeSheetFactory;
use App\Domain\AcademicPilotage\Services\GradeSheetProvisioningService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicPilotageBackfillServiceTest extends AcademicPilotageDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('systeme_academique')->nullable();
            $table->softDeletes();
        });

        Schema::create('esbtp_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->unsignedBigInteger('matiere_id')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id')->nullable();
            $table->unsignedBigInteger('enseignant_id')->nullable();
            $table->string('periode')->nullable();
            $table->string('type')->nullable();
            $table->dateTime('date_evaluation')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_dry_run_counts_missing_grade_sheets_without_writing(): void
    {
        $this->seedLegacyEvaluation();

        $result = $this->service()->run([
            'dry_run' => true,
            'limit' => 10,
        ], $this->actor(50));

        $this->assertSame(1, $result['scanned']);
        $this->assertSame(1, $result['would_create']);
        $this->assertSame(0, GradeSheet::query()->count());
    }

    public function test_backfill_creates_expected_sheet_and_entered_entries_without_validation(): void
    {
        $this->seedLegacyEvaluation();
        DB::table('esbtp_inscriptions')->insert([
            'etudiant_id' => 101,
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('esbtp_notes')->insert([
            'evaluation_id' => 77,
            'etudiant_id' => 101,
            'is_absent' => false,
            'created_by' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service()->run([
            'dry_run' => false,
            'limit' => 10,
        ], $this->actor(50));

        $sheet = GradeSheet::query()->sole();
        $entry = GradeSheetEntry::query()->sole();

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['entries_created']);
        $this->assertSame(GradeSheetStatus::EXPECTED, $sheet->status);
        $this->assertSame(GradeSheetEntryStatus::ENTERED, $entry->status);
    }

    public function test_real_backfill_command_requires_explicit_actor(): void
    {
        $this->seedLegacyEvaluation();

        $this->artisan('academic-pilotage:backfill')
            ->assertExitCode(1);
    }

    private function service(): AcademicPilotageBackfillService
    {
        $recorder = new GradeSheetEventRecorder;

        return new AcademicPilotageBackfillService(
            new GradeSheetProvisioningService(
                new GradeSheetFactory($recorder),
                new ExpectedGradeSheetEntrySynchronizer(
                    $recorder,
                    new AcademicMetricSnapshotInvalidationService(new AcademicPeriodNormalizer),
                ),
            ),
        );
    }

    private function seedLegacyEvaluation(): void
    {
        DB::table('esbtp_classes')->insert([
            'id' => 10,
            'systeme_academique' => 'BTS',
        ]);
        DB::table('esbtp_evaluations')->insert([
            'id' => 77,
            'classe_id' => 10,
            'matiere_id' => 30,
            'annee_universitaire_id' => 20,
            'periode' => 'semestre1',
            'type' => 'devoir',
            'date_evaluation' => '2026-07-20 08:00:00',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
