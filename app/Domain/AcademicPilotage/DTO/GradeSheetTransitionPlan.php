<?php

namespace App\Domain\AcademicPilotage\DTO;

use App\Domain\AcademicPilotage\Enums\GradeSheetAction;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;

final class GradeSheetTransitionPlan
{
    public function __construct(
        public readonly GradeSheetStatus $from,
        public readonly GradeSheetStatus $to,
        public readonly GradeSheetAction $action,
        public readonly ?string $timestampField,
        public readonly ?string $actorField,
    ) {}
}
