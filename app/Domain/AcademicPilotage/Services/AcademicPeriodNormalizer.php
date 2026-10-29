<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use InvalidArgumentException;

final class AcademicPeriodNormalizer
{
    private const ANNUAL_ALIASES = ['annual', 'annee', 'annuel'];

    public function normalize(string|int $period): string
    {
        $key = trim((string) $period);
        $key = str_replace(["\u{00E9}", "\u{00C9}"], 'e', $key);
        $key = strtolower($key);
        $key = str_replace([' ', '-', '_'], '', $key);

        if (in_array($key, self::ANNUAL_ALIASES, true)) {
            return 'annuel';
        }

        if (preg_match('/^(?:s|semester|semestre)?(\d{1,2})$/', $key, $matches) === 1) {
            $semester = (int) $matches[1];
            if ($semester >= 1 && $semester <= 14) {
                return 'semestre'.$semester;
            }
        }

        throw new InvalidArgumentException("La période académique '{$period}' n'est pas prise en charge.");
    }

    public function semesterNumber(string|int $period): ?int
    {
        $normalized = $this->normalize($period);

        return $normalized === 'annuel'
            ? null
            : (int) substr($normalized, strlen('semestre'));
    }

    public function previous(string|int $period): ?string
    {
        $semester = $this->semesterNumber($period);

        return $semester !== null && $semester > 1
            ? 'semestre'.($semester - 1)
            : null;
    }

    public function databaseVariants(string|int $period): array
    {
        $normalized = $this->normalize($period);
        if ($normalized === 'annuel') {
            return ['annuel', 'annual', 'annee'];
        }

        $semester = $this->semesterNumber($normalized);

        return array_values(array_unique([
            $normalized,
            (string) $semester,
            'S'.$semester,
            's'.$semester,
            'Semestre '.$semester,
            'semester'.$semester,
        ]));
    }
}
