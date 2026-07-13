<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Enums;

enum AcademicAlertType: string
{
    case ASSESSMENT_NOT_CONFIGURED = 'assessment_not_configured';
    case ASSESSMENT_NOT_CREATED = 'assessment_not_created';
    case MISSING_GRADE = 'missing_grade';
    case GRADE_SHEET_NOT_SUBMITTED = 'grade_sheet_not_submitted';
    case GRADE_SHEET_NOT_ENTERED = 'grade_sheet_not_entered';
    case CLASS_DELAYED = 'class_delayed';
    case STUDENT_NO_AVERAGE = 'student_no_average';
    case STUDENT_HIGH_ABSENCE = 'student_high_absence';
    case BULLETIN_BLOCKED = 'bulletin_blocked';
    case TEACHER_LATE = 'teacher_late';
    case EDUCATOR_OVERLOADED = 'educator_overloaded';
    case DATA_INCONSISTENCY = 'data_inconsistency';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
