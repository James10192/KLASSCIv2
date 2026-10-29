<?php

namespace App\Enums;

enum CashSessionStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case AUTO_CLOSED = 'auto_closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Ouverte',
            self::CLOSED => 'Clôturée',
            self::AUTO_CLOSED => 'Clôturée d\'office',
        };
    }

    public function isLocked(): bool
    {
        return $this !== self::OPEN;
    }
}
