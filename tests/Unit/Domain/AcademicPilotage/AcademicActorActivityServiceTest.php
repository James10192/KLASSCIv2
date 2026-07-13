<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Services\AcademicActorActivityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicActorActivityServiceTest extends AcademicPilotageDatabaseTestCase
{
    public function test_it_summarizes_real_entry_activity_within_the_selected_scope(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        DB::table('users')->insert([
            ['id' => 41, 'name' => 'Awa Koffi'],
            ['id' => 42, 'name' => 'Mariam Yao'],
        ]);

        $firstSheet = $this->createGradeSheet([
            'classe_id' => 10,
            'matiere_id' => 101,
            'annee_universitaire_id' => 20,
            'academic_system' => 'BTS',
            'semester' => 'semestre1',
            'entered_by' => 41,
            'entered_at' => now(),
        ]);
        $secondSheet = $this->createGradeSheet([
            'classe_id' => 11,
            'matiere_id' => 102,
            'annee_universitaire_id' => 20,
            'academic_system' => 'BTS',
            'semester' => 'semestre1',
            'entered_by' => 41,
            'entered_at' => now(),
        ]);
        $outsideScope = $this->createGradeSheet([
            'classe_id' => 99,
            'matiere_id' => 999,
            'annee_universitaire_id' => 20,
            'academic_system' => 'BTS',
            'semester' => 'semestre1',
            'entered_by' => 42,
            'entered_at' => now(),
        ]);

        $this->createEntry($firstSheet, 1, [
            'status' => GradeSheetEntryStatus::ENTERED->value,
            'entered_by' => 41,
        ]);
        $this->createEntry($firstSheet, 2, [
            'status' => GradeSheetEntryStatus::ENTERED->value,
            'entered_by' => 41,
        ]);
        $this->createEntry($secondSheet, 3, [
            'status' => GradeSheetEntryStatus::ENTERED->value,
            'entered_by' => 41,
        ]);
        $this->createEntry($outsideScope, 4, [
            'status' => GradeSheetEntryStatus::ENTERED->value,
            'entered_by' => 42,
        ]);

        $result = app(AcademicActorActivityService::class)->summarize(
            20,
            'semestre1',
            'BTS',
            null,
            collect([10, 11]),
            41,
            'Awa Koffi',
        );

        $this->assertCount(1, $result['actors']);
        $this->assertSame(41, $result['actors'][0]['id']);
        $this->assertSame(3, $result['actors'][0]['notes_entered']);
        $this->assertSame(2, $result['actors'][0]['subjects_count']);
        $this->assertSame(2, $result['actors'][0]['classes_count']);
        $this->assertSame(2, $result['actors'][0]['sheets_completed']);
        $this->assertSame($result['actors'][0], $result['current_actor']);
    }

    public function test_it_returns_an_empty_current_actor_when_no_activity_exists(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        $result = app(AcademicActorActivityService::class)->summarize(
            20,
            'semestre1',
            null,
            null,
            collect(),
            77,
            'Mon compte',
        );

        $this->assertSame([], $result['actors']);
        $this->assertSame(77, $result['current_actor']['id']);
        $this->assertSame('Mon compte', $result['current_actor']['name']);
        $this->assertSame(0, $result['current_actor']['notes_entered']);
    }
}
