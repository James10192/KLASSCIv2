<?php

namespace Tests\Unit\Policies;

use App\Domain\AcademicPilotage\Models\AcademicActorAssignment;
use App\Models\User;
use App\Policies\AcademicActorAssignmentPolicy;
use PHPUnit\Framework\TestCase;

class AcademicActorAssignmentPolicyTest extends TestCase
{
    public function test_assignment_management_requires_the_assign_permission(): void
    {
        $policy = new AcademicActorAssignmentPolicy;
        $assignment = $this->createMock(AcademicActorAssignment::class);

        $this->assertTrue($policy->create($this->userWithPermission(true)));
        $this->assertTrue($policy->delete($this->userWithPermission(true), $assignment));
        $this->assertFalse($policy->create($this->userWithPermission(false)));
        $this->assertFalse($policy->delete($this->userWithPermission(false), $assignment));
    }

    private function userWithPermission(bool $allowed): User
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['can'])
            ->getMock();
        $user->method('can')
            ->with('academic_sheets.assign')
            ->willReturn($allowed);

        return $user;
    }
}
