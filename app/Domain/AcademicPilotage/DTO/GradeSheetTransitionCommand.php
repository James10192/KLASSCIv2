<?php

namespace App\Domain\AcademicPilotage\DTO;

use App\Domain\AcademicPilotage\Enums\GradeSheetAction;

final class GradeSheetTransitionCommand
{
    public function __construct(
        public readonly int $gradeSheetId,
        public readonly GradeSheetAction $action,
        public readonly int $expectedLockVersion,
        public readonly int $actorId,
        public readonly ?string $reason = null,
        public readonly array $metadata = [],
    ) {}
}
