<?php

namespace App\Policies;

use App\Domain\AcademicPilotage\Models\GradeSheetDocument;
use App\Models\User;

class GradeSheetDocumentPolicy
{
    public function __construct(private GradeSheetPolicy $gradeSheetPolicy) {}

    public function view(User $user, GradeSheetDocument $document): bool
    {
        return $this->viewDocument($user, $document);
    }

    public function viewDocument(User $user, GradeSheetDocument $document): bool
    {
        $sheet = $document->gradeSheet;

        return $sheet !== null && $this->gradeSheetPolicy->view($user, $sheet);
    }
}
