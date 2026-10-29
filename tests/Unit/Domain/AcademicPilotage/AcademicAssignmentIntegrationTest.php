<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\AcademicResponsibility;
use App\Domain\AcademicPilotage\Models\AcademicActorAssignment;
use App\Domain\AcademicPilotage\Services\AcademicActorScopeService;
use App\Domain\AcademicPilotage\Services\AcademicAssignmentService;
use App\Models\User;

class AcademicAssignmentIntegrationTest extends AcademicPilotageDatabaseTestCase
{
    public function test_assignment_is_scoped_reusable_and_deactivatable(): void
    {
        $service = new AcademicAssignmentService;
        $actor = $this->actor(50);
        $assigned = $service->assign(
            60,
            10,
            20,
            AcademicResponsibility::GRADE_ENTRY,
            $actor,
            ['source' => 'direction'],
        );
        $updated = $service->assign(
            60,
            10,
            20,
            AcademicResponsibility::GRADE_ENTRY,
            $this->actor(51),
            ['source' => 'coordination'],
        );

        $this->assertSame($assigned->id, $updated->id);
        $this->assertSame(1, AcademicActorAssignment::query()->count());
        $this->assertSame(50, $updated->created_by);
        $this->assertSame(51, $updated->updated_by);
        $this->assertSame(['source' => 'coordination'], $updated->metadata);
        $this->assertTrue($updated->is_active);

        $user = $this->scopedUser(60);
        $scope = new AcademicActorScopeService;
        $sheet = $this->createGradeSheet();
        $this->assertTrue($scope->hasSheetScope(
            $user,
            $sheet,
            AcademicResponsibility::GRADE_ENTRY,
        ));
        $this->assertFalse($scope->hasSheetScope(
            $user,
            $sheet,
            AcademicResponsibility::GRADE_CONTROL,
        ));

        $service->deactivate($updated, $this->actor(52));

        $this->assertFalse($scope->hasSheetScope(
            $user,
            $sheet,
            AcademicResponsibility::GRADE_ENTRY,
        ));
    }

    private function scopedUser(int $id): User
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['can'])
            ->getMock();
        $user->method('can')->willReturn(false);
        $user->setRawAttributes(['id' => $id], true);
        $user->exists = true;

        return $user;
    }
}
