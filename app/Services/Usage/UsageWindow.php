<?php

namespace App\Services\Usage;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Periode analysee par le rapport d'usage, bornes incluses.
 */
final class UsageWindow
{
    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {
        if ($to->lessThan($from)) {
            throw new InvalidArgumentException('La date de fin precede la date de debut.');
        }
    }

    public static function fromDates(string $from, string $to): self
    {
        return new self(
            CarbonImmutable::parse($from)->startOfDay(),
            CarbonImmutable::parse($to)->endOfDay(),
        );
    }

    public function days(): int
    {
        return $this->from->diffInDays($this->to) + 1;
    }

    /** Nombre de semaines calendaires (lundi) touchees par la periode. */
    public function weekStarts(): array
    {
        $weeks = [];
        $cursor = $this->from->startOfWeek();
        while ($cursor->lessThanOrEqualTo($this->to)) {
            $weeks[] = $cursor->toDateString();
            $cursor = $cursor->addWeek();
        }

        return $weeks;
    }

    public function toArray(): array
    {
        return [
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'days' => $this->days(),
        ];
    }
}
