<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use InvalidArgumentException;

final class AcademicSystemNormalizer
{
    public const BTS = 'BTS';

    public const LMD = 'LMD';

    public function normalize(?string $system): string
    {
        $normalized = strtoupper(trim((string) $system));

        if ($normalized === '') {
            return self::BTS;
        }

        if (! in_array($normalized, [self::BTS, self::LMD], true)) {
            throw new InvalidArgumentException(
                "Le système académique de la classe n'est pas pris en charge.",
            );
        }

        return $normalized;
    }

    public function assertMatches(?string $actual, string $requested): string
    {
        $actual = $this->normalize($actual);
        $requested = $this->normalize($requested);

        if ($requested !== $actual) {
            throw new InvalidArgumentException(
                'Le système académique demandé ne correspond pas à celui de la classe.',
            );
        }

        return $actual;
    }
}
