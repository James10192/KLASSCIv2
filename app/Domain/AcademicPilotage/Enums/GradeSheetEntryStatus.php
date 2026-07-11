<?php

namespace App\Domain\AcademicPilotage\Enums;

enum GradeSheetEntryStatus: string
{
    case EXPECTED = 'expected';
    case ENTERED = 'entered';
    case ABSENT = 'absent';
    case EXEMPT = 'exempt';
    case NOT_APPLICABLE = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::EXPECTED => 'Attendue',
            self::ENTERED => 'Saisie',
            self::ABSENT => 'Absent',
            self::EXEMPT => 'Dispensé',
            self::NOT_APPLICABLE => 'Non applicable',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
