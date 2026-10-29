<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicActorScopeService;
use Illuminate\Support\Facades\DB;

class AcademicActorDashboardScopeTest extends AcademicPilotageDatabaseTestCase
{
    public function test_it_combines_explicit_and_observed_academic_activity(): void
    {
        $user = $this->actor(60);
        DB::table('esbtp_academic_actor_assignments')->insert([
            'user_id' => 60, 'classe_id' => 10, 'annee_universitaire_id' => 20,
            'responsibility' => 'grade_entry', 'is_active' => true,
        ]);
        $this->createGradeSheet(['classe_id' => 11, 'annee_universitaire_id' => 20, 'entered_by' => 60]);
        DB::table('esbtp_evaluations')->insert([
            'id' => 30, 'classe_id' => 12, 'annee_universitaire_id' => 20, 'enseignant_id' => 60,
        ]);
        DB::table('esbtp_notes')->insert([
            'evaluation_id' => 30, 'etudiant_id' => 90, 'created_by' => null, 'updated_by' => 60,
            'is_absent' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $scope = (new AcademicActorScopeService)->dashboardScope($user, 20);

        $this->assertFalse($scope->global);
        $this->assertSame([10, 11, 12], $scope->classIds->sort()->values()->all());
        $this->assertContains('assignment', $scope->sources);
        $this->assertContains('grade_sheet_activity', $scope->sources);
        $this->assertContains('evaluation_activity', $scope->sources);
        $this->assertContains('grade_entry_activity', $scope->sources);
    }

    public function test_it_recognizes_a_class_from_a_course_session(): void
    {
        $user = $this->actor(60);
        DB::table('esbtp_teachers')->insert(['id' => 70, 'user_id' => 60]);
        DB::table('esbtp_emploi_temps')->insert(['id' => 80, 'annee_universitaire_id' => 20]);
        DB::table('esbtp_seance_cours')->insert([
            'emploi_temps_id' => 80, 'classe_id' => 13, 'teacher_id' => 70,
        ]);

        $scope = (new AcademicActorScopeService)->dashboardScope($user, 20);

        $this->assertSame([13], $scope->classIds->all());
        $this->assertContains('teaching_activity', $scope->sources);
    }
}
