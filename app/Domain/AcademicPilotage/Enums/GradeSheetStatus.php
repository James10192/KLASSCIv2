<?php

namespace App\Domain\AcademicPilotage\Enums;

enum GradeSheetStatus: string
{
    case EXPECTED = 'expected';
    case SUBMITTED = 'submitted';
    case RECEIVED = 'received';
    case IN_ENTRY = 'in_entry';
    case ENTERED = 'entered';
    case CONTROLLED = 'controlled';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';
    case CORRECTION_REQUESTED = 'correction_requested';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::EXPECTED => 'Attendue',
            self::SUBMITTED => 'Soumise',
            self::RECEIVED => 'Reçue',
            self::IN_ENTRY => 'En cours de saisie',
            self::ENTERED => 'Saisie',
            self::CONTROLLED => 'Contrôlée',
            self::VALIDATED => 'Validée',
            self::REJECTED => 'Rejetée',
            self::CORRECTION_REQUESTED => 'Correction demandée',
            self::CANCELLED => 'Annulée',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::VALIDATED, self::REJECTED, self::CANCELLED], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
