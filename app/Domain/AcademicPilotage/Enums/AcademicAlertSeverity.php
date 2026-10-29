<?php

namespace App\Domain\AcademicPilotage\Enums;

enum AcademicAlertSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';
    case BLOCKING = 'blocking';

    public function label(): string
    {
        return match ($this) {
            self::INFO => 'Information',
            self::WARNING => 'Avertissement',
            self::CRITICAL => 'Critique',
            self::BLOCKING => 'Bloquante',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
