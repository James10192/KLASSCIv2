<?php

namespace App\Policies;

use App\Domain\AcademicPilotage\Models\AcademicActorAssignment;
use App\Models\User;

class AcademicActorAssignmentPolicy
{
    public function create(User $user): bool
    {
        return $user->can('academic_sheets.assign');
    }

    public function delete(User $user, AcademicActorAssignment $assignment): bool
    {
        return $user->can('academic_sheets.assign');
    }
}
