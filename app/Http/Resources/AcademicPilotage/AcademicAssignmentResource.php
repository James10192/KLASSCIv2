<?php

namespace App\Http\Resources\AcademicPilotage;

use Illuminate\Http\Resources\Json\JsonResource;

class AcademicAssignmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'classe_id' => $this->classe_id,
            'annee_universitaire_id' => $this->annee_universitaire_id,
            'responsibility' => $this->responsibility?->value,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
