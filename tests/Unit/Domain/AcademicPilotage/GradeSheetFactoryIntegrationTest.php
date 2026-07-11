<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryMode;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetEntry;
use App\Domain\AcademicPilotage\Models\GradeSheetEvent;
use App\Domain\AcademicPilotage\Services\ExpectedGradeSheetEntrySynchronizer;
use App\Domain\AcademicPilotage\Services\GradeSheetEventRecorder;
use App\Domain\AcademicPilotage\Services\GradeSheetFactory;
use App\Domain\AcademicPilotage\Services\GradeSheetProvisioningService;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use Illuminate\Support\Facades\DB;

class GradeSheetFactoryIntegrationTest extends AcademicPilotageDatabaseTestCase
{
    public function test_creation_from_evaluation_is_complete_and_idempotent(): void
    {
        DB::table('esbtp_teachers')->insert(['id' => 5, 'user_id' => 8]);
        $evaluation = $this->evaluation();
        $factory = new GradeSheetFactory(new GradeSheetEventRecorder);

        $created = $factory->createFromEvaluation(
            $evaluation,
            GradeSheetEntryMode::PAPER,
            $this->actor(50),
        );
        $existing = $factory->createFromEvaluation(
            $evaluation,
            GradeSheetEntryMode::DIRECT,
            $this->actor(51),
        );

        $this->assertSame($created->id, $existing->id);
        $this->assertSame(1, GradeSheet::query()->count());
        $this->assertSame(1, GradeSheetEvent::query()->count());
        $this->assertSame(GradeSheetStatus::EXPECTED, $created->status);
        $this->assertSame(GradeSheetEntryMode::PAPER, $created->entry_mode);
        $this->assertSame(5, $created->teacher_id);
        $this->assertSame('LMD', $created->academic_system);
        $this->assertSame('semestre1', $created->semester);
        $this->assertSame('devoir', $created->evaluation_type);
        $this->assertSame(1, $created->lock_version);
        $this->assertSame(50, $created->created_by);
        $this->assertSame('created', GradeSheetEvent::query()->sole()->event_type);
    }

    public function test_provisioning_synchronizes_entries_once(): void
    {
        DB::table('esbtp_teachers')->insert(['id' => 5, 'user_id' => 8]);
        DB::table('esbtp_inscriptions')->insert([
            'etudiant_id' => 101,
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $recorder = new GradeSheetEventRecorder;
        $service = new GradeSheetProvisioningService(
            new GradeSheetFactory($recorder),
            new ExpectedGradeSheetEntrySynchronizer($recorder),
        );

        $first = $service->provision(
            $this->evaluation(),
            GradeSheetEntryMode::DIRECT,
            $this->actor(50),
        );
        $second = $service->provision(
            $this->evaluation(),
            GradeSheetEntryMode::PAPER,
            $this->actor(51),
        );

        $this->assertTrue($first->created);
        $this->assertNotNull($first->entrySync);
        $this->assertFalse($second->created);
        $this->assertNull($second->entrySync);
        $this->assertSame(1, GradeSheet::query()->count());
        $this->assertSame(1, GradeSheetEntry::query()->count());
        $this->assertSame(2, GradeSheetEvent::query()->count());
        $this->assertSame(2, $second->gradeSheet->lock_version);
    }

    private function evaluation(): ESBTPEvaluation
    {
        $evaluation = new ESBTPEvaluation;
        $evaluation->setRawAttributes([
            'id' => 77,
            'classe_id' => 10,
            'matiere_id' => 30,
            'annee_universitaire_id' => 20,
            'enseignant_id' => 8,
            'periode' => 'semestre1',
            'type' => 'devoir',
            'date_evaluation' => '2026-07-20 08:00:00',
        ], true);
        $evaluation->exists = true;

        $classe = new ESBTPClasse;
        $classe->setRawAttributes(['id' => 10, 'systeme_academique' => 'LMD'], true);
        $classe->exists = true;
        $evaluation->setRelation('classe', $classe);

        return $evaluation;
    }
}
