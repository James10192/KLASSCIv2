<?php

namespace App\Domain\AcademicPilotage\Enums;

enum AcademicAlertStatus: string
{
    case OPEN = 'open';
    case ACKNOWLEDGED = 'acknowledged';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Ouverte',
            self::ACKNOWLEDGED => 'Prise en compte',
            self::IN_PROGRESS => 'En cours de traitement',
            self::RESOLVED => 'Résolue',
            self::DISMISSED => 'Classée sans suite',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::OPEN, self::ACKNOWLEDGED, self::IN_PROGRESS], true);
    }

    public function isTerminal(): bool
    {
        return ! $this->isOpen();
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
