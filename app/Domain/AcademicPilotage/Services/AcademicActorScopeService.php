<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\AcademicActorScopeResolution;
use App\Domain\AcademicPilotage\Enums\AcademicResponsibility;
use App\Domain\AcademicPilotage\Models\AcademicActorAssignment;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Models\ESBTPEvaluation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /**
     * Null means that the actor has a global academic-pilotage scope.
     * An empty collection intentionally produces an empty dashboard.
     */
    public function dashboardClassIds(User $user, ?int $academicYearId): ?Collection
    {
        $scope = $this->dashboardScope($user, $academicYearId);

        return $scope->global ? null : $scope->classIds;
    }

    public function dashboardScope(User $user, ?int $academicYearId): AcademicActorScopeResolution
    {
        if ($this->hasGlobalScope($user)) {
            return AcademicActorScopeResolution::global();
        }

        if ($user->getKey() === null || $academicYearId === null) {
            return new AcademicActorScopeResolution(false, collect(), []);
        }

        $sources = [];
        $classIds = collect();
        $this->mergeEvidence($classIds, $sources, 'assignment', AcademicActorAssignment::query()
            ->where('user_id', $user->getKey())
            ->where('annee_universitaire_id', $academicYearId)
            ->where('is_active', true)
            ->pluck('classe_id'));

        $actorColumns = ['assigned_processor_id', 'submitted_by', 'received_by', 'entered_by', 'controlled_by', 'validated_by'];
        $this->mergeEvidence($classIds, $sources, 'grade_sheet_activity', GradeSheet::query()
            ->where('annee_universitaire_id', $academicYearId)
            ->where(fn ($query) => collect($actorColumns)->each(
                fn (string $column, int $index) => $index === 0
                    ? $query->where($column, $user->getKey())
                    : $query->orWhere($column, $user->getKey())
            ))
            ->pluck('classe_id'));

        $teacherId = $user->teacherProfile?->getKey();
        if ($teacherId !== null) {
            $this->mergeEvidence($classIds, $sources, 'teaching_activity', GradeSheet::query()
                ->where('annee_universitaire_id', $academicYearId)
                ->where('teacher_id', $teacherId)
                ->pluck('classe_id'));
            $this->mergeEvidence($classIds, $sources, 'teaching_activity', DB::table('esbtp_seance_cours')
                ->join('esbtp_emploi_temps', 'esbtp_seance_cours.emploi_temps_id', '=', 'esbtp_emploi_temps.id')
                ->where('esbtp_seance_cours.teacher_id', $teacherId)
                ->where('esbtp_emploi_temps.annee_universitaire_id', $academicYearId)
                ->pluck('esbtp_seance_cours.classe_id'));
        }

        $this->mergeEvidence($classIds, $sources, 'evaluation_activity', DB::table('esbtp_evaluations')
            ->where('annee_universitaire_id', $academicYearId)
            ->where('enseignant_id', $user->getKey())
            ->pluck('classe_id'));
        $this->mergeEvidence($classIds, $sources, 'grade_entry_activity', DB::table('esbtp_notes')
            ->join('esbtp_evaluations', 'esbtp_notes.evaluation_id', '=', 'esbtp_evaluations.id')
            ->where('esbtp_notes.created_by', $user->getKey())
            ->where('esbtp_evaluations.annee_universitaire_id', $academicYearId)
            ->pluck('esbtp_evaluations.classe_id'));

        return new AcademicActorScopeResolution(
            false,
            $classIds->filter()->map(fn ($id): int => (int) $id)->unique()->values(),
            array_values(array_unique($sources)),
        );
    }

    private function mergeEvidence(Collection $classIds, array &$sources, string $source, Collection $evidence): void
    {
        if ($evidence->filter()->isEmpty()) {
            return;
        }

        $classIds->push(...$evidence);
        $sources[] = $source;
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
