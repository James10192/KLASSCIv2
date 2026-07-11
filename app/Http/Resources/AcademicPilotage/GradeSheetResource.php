<?php

namespace App\Http\Resources\AcademicPilotage;

use Illuminate\Http\Resources\Json\JsonResource;

class GradeSheetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'evaluation_id' => $this->evaluation_id,
            'classe_id' => $this->classe_id,
            'matiere_id' => $this->matiere_id,
            'annee_universitaire_id' => $this->annee_universitaire_id,
            'teacher_id' => $this->teacher_id,
            'assigned_processor_id' => $this->assigned_processor_id,
            'academic_system' => $this->academic_system,
            'semester' => $this->semester,
            'evaluation_type' => $this->evaluation_type,
            'entry_mode' => $this->entry_mode?->value,
            'status' => $this->status?->value,
            'expected_at' => $this->expected_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'received_at' => $this->received_at?->toIso8601String(),
            'entry_started_at' => $this->entry_started_at?->toIso8601String(),
            'entered_at' => $this->entered_at?->toIso8601String(),
            'controlled_at' => $this->controlled_at?->toIso8601String(),
            'validated_at' => $this->validated_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'source' => $this->source,
            'observations' => $this->observations,
            'metadata' => $this->metadata,
            'lock_version' => $this->lock_version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
