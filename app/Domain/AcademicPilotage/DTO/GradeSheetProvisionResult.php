<?php

namespace App\Domain\AcademicPilotage\DTO;

use App\Domain\AcademicPilotage\Models\GradeSheet;

final class GradeSheetProvisionResult
{
    public function __construct(
        public readonly GradeSheet $gradeSheet,
        public readonly ?EntrySyncResult $entrySync,
        public readonly bool $created,
    ) {}
}
