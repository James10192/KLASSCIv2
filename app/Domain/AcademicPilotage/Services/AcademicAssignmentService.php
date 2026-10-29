<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Enums\AcademicResponsibility;
use App\Domain\AcademicPilotage\Models\AcademicActorAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AcademicAssignmentService
{
    public function assign(
        int $userId,
        int $classeId,
        int $anneeId,
        AcademicResponsibility $responsibility,
        User $actor,
        array $metadata = []
    ): AcademicActorAssignment {
        return DB::transaction(function () use (
            $userId,
            $classeId,
            $anneeId,
            $responsibility,
            $actor,
            $metadata
        ) {
            $scope = [
                'user_id' => $userId,
                'classe_id' => $classeId,
                'annee_universitaire_id' => $anneeId,
                'responsibility' => $responsibility->value,
            ];
            $assignment = AcademicActorAssignment::query()->firstOrNew($scope);

            $assignment->forceFill([
                ...$scope,
                'is_active' => true,
                'metadata' => $metadata ?: null,
                'created_by' => $assignment->exists ? $assignment->created_by : $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            return $assignment->refresh();
        });
    }

    public function deactivate(
        AcademicActorAssignment $assignment,
        User $actor
    ): AcademicActorAssignment {
        return DB::transaction(function () use ($assignment, $actor) {
            $current = AcademicActorAssignment::query()
                ->lockForUpdate()
                ->findOrFail($assignment->id);

            $current->forceFill([
                'is_active' => false,
                'updated_by' => $actor->id,
            ])->save();

            return $current->refresh();
        });
    }
}
