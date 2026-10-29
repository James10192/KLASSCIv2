<?php

namespace App\Domain\AcademicPilotage\Enums;

enum GradeSheetEntryMode: string
{
    case DIRECT = 'direct';
    case PAPER = 'paper';

    public function label(): string
    {
        return match ($this) {
            self::DIRECT => 'Saisie directe',
            self::PAPER => 'Fiche papier',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
