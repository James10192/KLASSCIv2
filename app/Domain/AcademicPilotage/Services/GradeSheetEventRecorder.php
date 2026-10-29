<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetEvent;

class GradeSheetEventRecorder
{
    public function record(
        GradeSheet $sheet,
        string $eventType,
        ?GradeSheetStatus $fromStatus,
        ?GradeSheetStatus $toStatus,
        int $actorId,
        ?string $reason = null,
        array $metadata = []
    ): GradeSheetEvent {
        $event = new GradeSheetEvent;
        $event->forceFill([
            'grade_sheet_id' => $sheet->id,
            'event_type' => $eventType,
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus?->value,
            'actor_id' => $actorId,
            'reason' => $reason,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ])->save();

        return $event;
    }
}
