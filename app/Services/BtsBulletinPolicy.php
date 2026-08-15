<?php

namespace App\Services;

/**
 * Règles de bulletin BTS configurables par tenant.
 *
 * Les clés de réglages restent volontairement séparées par année BTS afin
 * qu'une école puisse appliquer ses décisions sans affecter les autres.
 */
final class BtsBulletinPolicy
{
    public static function annualWeights(
        bool $isBts,
        ?int $levelYear,
        array $settings,
        array $fallback,
    ): array {
        if (! $isBts || ! in_array($levelYear, [1, 2], true)) {
            return $fallback;
        }

        $prefix = "bulletin_bts{$levelYear}_";
        $semester1 = self::nonNegativeWeight($settings[$prefix . 'semester1_weight'] ?? null, $fallback['semester1']);
        $semester2 = self::nonNegativeWeight($settings[$prefix . 'semester2_weight'] ?? null, $fallback['semester2']);

        if ($semester1 + $semester2 <= 0) {
            return $fallback;
        }

        return ['semester1' => $semester1, 'semester2' => $semester2];
    }

    public static function councilDecision(
        bool $isBts,
        ?int $levelYear,
        string $period,
        ?float $annualAverage,
        array $settings,
    ): ?string {
        if (! $isBts || ! in_array($levelYear, [1, 2], true) || $period !== 'semestre2') {
            return null;
        }

        $prefix = "bulletin_bts{$levelYear}_council_";
        $mode = $settings[$prefix . 'mode'] ?? 'manual';

        if ($mode === 'fixed') {
            return self::textOrNull($settings[$prefix . 'fixed_text'] ?? null);
        }

        if ($mode !== 'threshold' || $annualAverage === null) {
            return null;
        }

        $threshold = (float) ($settings[$prefix . 'threshold'] ?? 10);
        $key = $annualAverage >= $threshold ? 'at_or_above_text' : 'below_text';

        return self::textOrNull($settings[$prefix . $key] ?? null);
    }

    public static function decisionAverage(string $source, ?float $semester2Average, ?float $annualAverage): ?float
    {
        return $source === 'annual' ? $annualAverage : $semester2Average;
    }

    private static function nonNegativeWeight(mixed $value, float $fallback): float
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return max(0.0, (float) $value);
    }

    private static function textOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
