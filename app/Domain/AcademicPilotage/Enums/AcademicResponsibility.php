<?php

namespace App\Domain\AcademicPilotage\Enums;

enum AcademicResponsibility: string
{
    case GRADE_ENTRY = 'grade_entry';
    case SHEET_RECEPTION = 'sheet_reception';
    case GRADE_CONTROL = 'grade_control';
    case ACADEMIC_FOLLOWUP = 'academic_followup';

    public function label(): string
    {
        return match ($this) {
            self::GRADE_ENTRY => 'Saisie des notes',
            self::SHEET_RECEPTION => 'Réception des fiches',
            self::GRADE_CONTROL => 'Contrôle des notes',
            self::ACADEMIC_FOLLOWUP => 'Suivi académique',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
