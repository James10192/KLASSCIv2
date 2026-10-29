<?php

namespace App\Policies;

use App\Domain\AcademicPilotage\Enums\AcademicResponsibility;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Services\AcademicActorScopeService;
use App\Models\ESBTPEvaluation;
use App\Models\User;

class GradeSheetPolicy
{
    public function __construct(private AcademicActorScopeService $scopeService) {}

    public function viewAny(User $user): bool
    {
        return $this->scopeService->hasGlobalScope($user)
            || $user->can('academic_sheets.view_own');
    }

    public function view(User $user, GradeSheet $sheet): bool
    {
        return $this->scopeService->hasGlobalScope($user)
            || ($user->can('academic_sheets.view_own')
                && $this->scopeService->hasSheetScope($user, $sheet, allowTeacher: true));
    }

    public function create(User $user): bool
    {
        return $user->can('academic_sheets.create');
    }

    public function createForEvaluation(User $user, ESBTPEvaluation $evaluation): bool
    {
        return $user->can('academic_sheets.create')
            && $this->scopeService->hasEvaluationScope(
                $user,
                $evaluation,
                AcademicResponsibility::GRADE_ENTRY,
            );
    }

    public function submit(User $user, GradeSheet $sheet): bool
    {
        return $user->can('academic_sheets.submit')
            && $this->scopeService->hasSheetScope(
                $user,
                $sheet,
                AcademicResponsibility::GRADE_ENTRY,
                true
            );
    }

    public function receive(User $user, GradeSheet $sheet): bool
    {
        return $user->can('academic_sheets.receive')
            && $this->scopeService->hasSheetScope(
                $user,
                $sheet,
                AcademicResponsibility::SHEET_RECEPTION
            );
    }

    public function enter(User $user, GradeSheet $sheet): bool
    {
        return $user->can('academic_sheets.enter')
            && $this->scopeService->hasSheetScope(
                $user,
                $sheet,
                AcademicResponsibility::GRADE_ENTRY,
                true
            );
    }

    public function control(User $user, GradeSheet $sheet): bool
    {
        return $user->can('academic_sheets.control')
            && $this->scopeService->hasSheetScope(
                $user,
                $sheet,
                AcademicResponsibility::GRADE_CONTROL
            );
    }

    public function validate(User $user, GradeSheet $sheet): bool
    {
        return $user->can('academic_sheets.validate')
            && $this->scopeService->hasSheetScope(
                $user,
                $sheet,
                AcademicResponsibility::GRADE_CONTROL
            );
    }

    public function syncEntries(User $user, GradeSheet $sheet): bool
    {
        return $sheet->status->allowsEntrySynchronization()
            && $this->enter($user, $sheet);
    }

    public function uploadDocument(User $user, GradeSheet $sheet): bool
    {
        return $sheet->status->allowsDocumentUpload()
            && ($this->submit($user, $sheet) || $this->enter($user, $sheet));
    }
}
