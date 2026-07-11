<?php

namespace Tests\Unit\Policies;

use App\Domain\AcademicPilotage\Enums\AcademicResponsibility;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetDocument;
use App\Domain\AcademicPilotage\Services\AcademicActorScopeService;
use App\Models\ESBTPEvaluation;
use App\Models\User;
use App\Policies\GradeSheetDocumentPolicy;
use App\Policies\GradeSheetPolicy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GradeSheetPolicyTest extends TestCase
{
    public function test_view_any_accepts_global_or_scoped_view_permission(): void
    {
        foreach ([
            'academic_pilotage.view_all',
            'academic_sheets.view',
            'academic_sheets.view_own',
        ] as $permission) {
            $scope = $this->scopeService();
            $scope->method('hasGlobalScope')->willReturn(
                $permission !== 'academic_sheets.view_own'
            );

            $this->assertTrue(
                (new GradeSheetPolicy($scope))->viewAny($this->userWith($permission))
            );
        }
    }

    public function test_view_any_rejects_user_without_view_permission(): void
    {
        $scope = $this->scopeService();
        $scope->method('hasGlobalScope')->willReturn(false);

        $this->assertFalse((new GradeSheetPolicy($scope))->viewAny($this->userWith()));
    }

    public function test_global_view_permission_can_view_every_sheet(): void
    {
        $scope = $this->scopeService();
        $scope->expects($this->once())->method('hasGlobalScope')->willReturn(true);
        $scope->expects($this->never())->method('hasSheetScope');

        $this->assertTrue(
            (new GradeSheetPolicy($scope))->view(
                $this->userWith('academic_sheets.view'),
                $this->sheet()
            )
        );
    }

    public function test_view_own_requires_matching_sheet_scope(): void
    {
        $user = $this->userWith('academic_sheets.view_own');
        $sheet = $this->sheet();
        $scope = $this->scopeService();
        $scope->method('hasGlobalScope')->willReturn(false);
        $scope->expects($this->once())
            ->method('hasSheetScope')
            ->with($user, $sheet, null, true)
            ->willReturn(true);

        $this->assertTrue((new GradeSheetPolicy($scope))->view($user, $sheet));
    }

    public function test_assignment_scope_never_grants_view_permission(): void
    {
        $scope = $this->scopeService();
        $scope->method('hasGlobalScope')->willReturn(false);
        $scope->expects($this->never())->method('hasSheetScope');

        $this->assertFalse(
            (new GradeSheetPolicy($scope))->view($this->userWith(), $this->sheet())
        );
    }

    public function test_create_only_requires_its_permission(): void
    {
        $policy = new GradeSheetPolicy($this->scopeService());

        $this->assertTrue($policy->create($this->userWith('academic_sheets.create')));
        $this->assertFalse($policy->create($this->userWith()));
    }

    public function test_create_for_evaluation_requires_permission_and_scope(): void
    {
        $user = $this->userWith('academic_sheets.create');
        $evaluation = $this->evaluation();
        $scope = $this->scopeService();
        $scope->expects($this->once())
            ->method('hasEvaluationScope')
            ->with($user, $evaluation, AcademicResponsibility::GRADE_ENTRY)
            ->willReturn(true);

        $this->assertTrue(
            (new GradeSheetPolicy($scope))->createForEvaluation($user, $evaluation)
        );
    }

    public function test_create_for_evaluation_rejects_missing_permission_before_scope_lookup(): void
    {
        $scope = $this->scopeService();
        $scope->expects($this->never())->method('hasEvaluationScope');

        $this->assertFalse(
            (new GradeSheetPolicy($scope))->createForEvaluation(
                $this->userWith(),
                $this->evaluation(),
            )
        );
    }

    /**
     * @dataProvider scopedActionProvider
     */
    public function test_actions_require_permission_and_expected_scope(
        string $method,
        string $permission,
        AcademicResponsibility $responsibility,
        bool $allowTeacher
    ): void {
        $user = $this->userWith($permission);
        $sheet = $this->sheet();
        $scope = $this->scopeService();
        $scope->expects($this->once())
            ->method('hasSheetScope')
            ->with($user, $sheet, $responsibility, $allowTeacher)
            ->willReturn(true);

        $this->assertTrue((new GradeSheetPolicy($scope))->{$method}($user, $sheet));
    }

    /**
     * @dataProvider scopedActionProvider
     */
    public function test_actions_reject_matching_assignment_without_permission(
        string $method,
        string $permission,
        AcademicResponsibility $responsibility,
        bool $allowTeacher
    ): void {
        $scope = $this->scopeService();
        $scope->expects($this->never())->method('hasSheetScope');

        $this->assertFalse(
            (new GradeSheetPolicy($scope))->{$method}($this->userWith(), $this->sheet())
        );
    }

    /**
     * @dataProvider scopedActionProvider
     */
    public function test_actions_reject_permission_outside_expected_scope(
        string $method,
        string $permission,
        AcademicResponsibility $responsibility,
        bool $allowTeacher
    ): void {
        $scope = $this->scopeService();
        $scope->method('hasSheetScope')->willReturn(false);

        $this->assertFalse(
            (new GradeSheetPolicy($scope))->{$method}(
                $this->userWith($permission),
                $this->sheet()
            )
        );
    }

    public static function scopedActionProvider(): array
    {
        return [
            'submit by teacher or grade entry actor' => [
                'submit',
                'academic_sheets.submit',
                AcademicResponsibility::GRADE_ENTRY,
                true,
            ],
            'receive by reception actor' => [
                'receive',
                'academic_sheets.receive',
                AcademicResponsibility::SHEET_RECEPTION,
                false,
            ],
            'enter by teacher or grade entry actor' => [
                'enter',
                'academic_sheets.enter',
                AcademicResponsibility::GRADE_ENTRY,
                true,
            ],
            'control by grade control actor' => [
                'control',
                'academic_sheets.control',
                AcademicResponsibility::GRADE_CONTROL,
                false,
            ],
            'validate by grade control actor' => [
                'validate',
                'academic_sheets.validate',
                AcademicResponsibility::GRADE_CONTROL,
                false,
            ],
        ];
    }

    public function test_sync_entries_follows_enter_authorization(): void
    {
        $user = $this->userWith('academic_sheets.enter');
        $sheet = $this->sheet();
        $scope = $this->scopeService();
        $scope->expects($this->once())
            ->method('hasSheetScope')
            ->with($user, $sheet, AcademicResponsibility::GRADE_ENTRY, true)
            ->willReturn(true);

        $this->assertTrue((new GradeSheetPolicy($scope))->syncEntries($user, $sheet));
    }

    public function test_upload_document_accepts_submit_or_enter_authorization(): void
    {
        foreach (['academic_sheets.submit', 'academic_sheets.enter'] as $permission) {
            $scope = $this->scopeService();
            $scope->method('hasSheetScope')->willReturn(true);

            $this->assertTrue(
                (new GradeSheetPolicy($scope))->uploadDocument(
                    $this->userWith($permission),
                    $this->sheet()
                )
            );
        }
    }

    public function test_upload_document_rejects_user_without_submit_or_enter_permission(): void
    {
        $scope = $this->scopeService();
        $scope->expects($this->never())->method('hasSheetScope');

        $this->assertFalse(
            (new GradeSheetPolicy($scope))->uploadDocument($this->userWith(), $this->sheet())
        );
    }

    public function test_document_view_delegates_to_grade_sheet_view(): void
    {
        $user = $this->userWith('academic_sheets.view_own');
        $sheet = $this->sheet();
        $document = new GradeSheetDocument;
        $document->setRelation('gradeSheet', $sheet);

        $sheetPolicy = $this->createMock(GradeSheetPolicy::class);
        $sheetPolicy->expects($this->exactly(2))
            ->method('view')
            ->with($user, $sheet)
            ->willReturn(true);

        $policy = new GradeSheetDocumentPolicy($sheetPolicy);

        $this->assertTrue($policy->view($user, $document));
        $this->assertTrue($policy->viewDocument($user, $document));
    }

    private function scopeService(): AcademicActorScopeService&MockObject
    {
        return $this->createMock(AcademicActorScopeService::class);
    }

    private function userWith(string ...$permissions): User
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['can'])
            ->getMock();
        $user->method('can')->willReturnCallback(
            function ($abilities) use ($permissions): bool {
                if (is_array($abilities)) {
                    return count(array_intersect($abilities, $permissions)) > 0;
                }

                return in_array($abilities, $permissions, true);
            }
        );

        return $user;
    }

    private function sheet(): GradeSheet
    {
        $sheet = $this->getMockBuilder(GradeSheet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $sheet->setRawAttributes(['status' => 'expected'], true);

        return $sheet;
    }

    private function evaluation(): ESBTPEvaluation
    {
        return $this->getMockBuilder(ESBTPEvaluation::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
