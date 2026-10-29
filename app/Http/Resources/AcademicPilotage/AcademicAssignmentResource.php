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
            'responsibility_label' => $this->responsibility?->label(),
            'is_active' => $this->is_active,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'classe' => $this->whenLoaded('classe', fn () => [
                'id' => $this->classe?->id,
                'name' => trim(($this->classe?->code ? $this->classe->code.' · ' : '').$this->classe?->name),
            ]),
            'annee_universitaire' => $this->whenLoaded('anneeUniversitaire', fn () => [
                'id' => $this->anneeUniversitaire?->id,
                'name' => $this->anneeUniversitaire?->display_name,
            ]),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
