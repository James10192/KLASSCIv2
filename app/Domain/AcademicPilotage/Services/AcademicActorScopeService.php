<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Enums\AcademicResponsibility;
use App\Domain\AcademicPilotage\Models\AcademicActorAssignment;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Models\ESBTPEvaluation;
use App\Models\User;

class AcademicActorScopeService
{
    public function hasGlobalScope(User $user): bool
    {
        return $user->can('academic_pilotage.view_all')
            || $user->can('academic_sheets.view');
    }

    public function hasSheetScope(
        User $user,
        GradeSheet $sheet,
        ?AcademicResponsibility $responsibility = null,
        bool $allowTeacher = false
    ): bool {
        if ($this->hasGlobalScope($user)) {
            return true;
        }

        if ($allowTeacher && $this->isSheetTeacher($user, $sheet)) {
            return true;
        }

        return $this->hasActiveAssignment($user, $sheet, $responsibility);
    }

    public function hasEvaluationScope(
        User $user,
        ESBTPEvaluation $evaluation,
        AcademicResponsibility $responsibility,
    ): bool {
        if ($this->hasGlobalScope($user)) {
            return true;
        }

        if ((int) $evaluation->enseignant_id === (int) $user->getKey()) {
            return true;
        }

        if ($user->getKey() === null
            || $evaluation->classe_id === null
            || $evaluation->annee_universitaire_id === null) {
            return false;
        }

        return $this->hasAssignmentForScope(
            $user,
            $evaluation->classe_id,
            $evaluation->annee_universitaire_id,
            $responsibility,
        );
    }

    private function hasActiveAssignment(
        User $user,
        GradeSheet $sheet,
        ?AcademicResponsibility $responsibility
    ): bool {
        if ($user->getKey() === null
            || $sheet->classe_id === null
            || $sheet->annee_universitaire_id === null) {
            return false;
        }

        return $this->hasAssignmentForScope(
            $user,
            $sheet->classe_id,
            $sheet->annee_universitaire_id,
            $responsibility,
        );
    }

    private function hasAssignmentForScope(
        User $user,
        int $classeId,
        int $anneeId,
        ?AcademicResponsibility $responsibility,
    ): bool {
        return AcademicActorAssignment::query()
            ->where('user_id', $user->getKey())
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('is_active', true)
            ->when(
                $responsibility !== null,
                fn ($query) => $query->where('responsibility', $responsibility->value),
            )
            ->exists();
    }

    private function isSheetTeacher(User $user, GradeSheet $sheet): bool
    {
        if ($user->getKey() === null) {
            return false;
        }

        if ($sheet->teacher_id !== null) {
            if ($sheet->relationLoaded('teacher')) {
                return (int) $sheet->getRelation('teacher')?->user_id
                    === (int) $user->getKey();
            }

            return $sheet->teacher()
                ->where('user_id', $user->getKey())
                ->exists();
        }

        return $sheet->evaluation_id !== null
            && $sheet->evaluation()
                ->where('enseignant_id', $user->getKey())
                ->exists();
    }
}
