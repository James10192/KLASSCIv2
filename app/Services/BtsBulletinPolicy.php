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
    private const SETTING_DEFINITIONS = [
        'bulletin_bts1_semester1_weight' => ['value' => '1', 'type' => 'float', 'description' => 'Coefficient BTS 1 Semestre 1', 'validation_rules' => ['nullable', 'numeric', 'min:0']],
        'bulletin_bts1_semester2_weight' => ['value' => '2', 'type' => 'float', 'description' => 'Coefficient BTS 1 Semestre 2', 'validation_rules' => ['nullable', 'numeric', 'min:0']],
        'bulletin_bts2_semester1_weight' => ['value' => '1', 'type' => 'float', 'description' => 'Coefficient BTS 2 Semestre 1', 'validation_rules' => ['nullable', 'numeric', 'min:0']],
        'bulletin_bts2_semester2_weight' => ['value' => '1', 'type' => 'float', 'description' => 'Coefficient BTS 2 Semestre 2', 'validation_rules' => ['nullable', 'numeric', 'min:0']],
        'bulletin_bts1_council_mode' => ['value' => 'manual', 'type' => 'string', 'description' => 'Mode de décision BTS 1', 'validation_rules' => ['nullable', 'in:manual,threshold']],
        'bulletin_bts1_council_average_source' => ['value' => 'semestre2', 'type' => 'string', 'description' => 'Moyenne de décision BTS 1', 'validation_rules' => ['nullable', 'in:semestre2,annual']],
        'bulletin_bts1_council_threshold' => ['value' => '10', 'type' => 'float', 'description' => 'Seuil de décision BTS 1', 'validation_rules' => ['required_if:bulletin_bts1_council_mode,threshold', 'nullable', 'numeric', 'between:0,20']],
        'bulletin_bts1_council_below_text' => ['value' => 'Redouble la classe', 'type' => 'string', 'description' => 'Décision BTS 1 sous le seuil', 'validation_rules' => ['required_if:bulletin_bts1_council_mode,threshold', 'nullable', 'string', 'max:191']],
        'bulletin_bts1_council_at_or_above_text' => ['value' => 'Admis(e) en 2e Année BTS', 'type' => 'string', 'description' => 'Décision BTS 1 au seuil ou au-dessus', 'validation_rules' => ['required_if:bulletin_bts1_council_mode,threshold', 'nullable', 'string', 'max:191']],
        'bulletin_bts1_s1_council_title' => ['value' => 'Décision du conseil de classe', 'type' => 'string', 'description' => 'Titre du conseil BTS 1 semestre 1', 'validation_rules' => ['nullable', 'string', 'max:191']],
        'bulletin_bts2_council_mode' => ['value' => 'manual', 'type' => 'string', 'description' => 'Mode de décision BTS 2', 'validation_rules' => ['nullable', 'in:manual,fixed']],
        'bulletin_bts2_council_fixed_text' => ['value' => "Redouble en cas d'échec à l'examen du BTS", 'type' => 'string', 'description' => 'Décision fixe BTS 2', 'validation_rules' => ['required_if:bulletin_bts2_council_mode,fixed', 'nullable', 'string', 'max:191']],
    ];

    public static function settingDefinitions(): array
    {
        return self::SETTING_DEFINITIONS;
    }

    public static function defaultSettings(): array
    {
        return array_map(
            fn (array $definition) => $definition['value'],
            self::SETTING_DEFINITIONS
        );
    }

    public static function validationRules(): array
    {
        return array_map(
            fn (array $definition) => $definition['validation_rules'],
            self::SETTING_DEFINITIONS
        );
    }

    public static function readSettings(callable $reader): array
    {
        $settings = [];
        foreach (self::defaultSettings() as $key => $default) {
            $settings[$key] = $reader($key, $default);
        }

        return $settings;
    }

    public static function effectiveSettings(array $input, callable $reader): array
    {
        $settings = [];
        foreach (self::defaultSettings() as $key => $default) {
            $settings[$key] = array_key_exists($key, $input)
                ? $input[$key]
                : $reader($key, $default);
        }

        return $settings;
    }

    public static function invalidWeightPairYears(array $settings): array
    {
        $invalidYears = [];
        foreach ([1, 2] as $year) {
            $semester1Key = "bulletin_bts{$year}_semester1_weight";
            $semester2Key = "bulletin_bts{$year}_semester2_weight";
            if (
                trim((string) ($settings[$semester1Key] ?? '')) !== ''
                && trim((string) ($settings[$semester2Key] ?? '')) !== ''
                && ((float) $settings[$semester1Key] + (float) $settings[$semester2Key]) <= 0
            ) {
                $invalidYears[] = $year;
            }
        }

        return $invalidYears;
    }

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
        if (! self::usesCouncilPolicy($isBts, $levelYear, $period)) {
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

    public static function usesCouncilPolicy(bool $isBts, ?int $levelYear, string $period): bool
    {
        return $isBts && in_array($levelYear, [1, 2], true) && $period === 'semestre2';
    }

    public static function displayCouncilDecision(
        bool $isBts,
        ?int $levelYear,
        string $period,
        ?string $configuredDecision,
        mixed $storedDecision,
    ): ?string {
        if (self::usesCouncilPolicy($isBts, $levelYear, $period)) {
            return $configuredDecision ?? '';
        }

        return $configuredDecision ?? self::textOrNull($storedDecision);
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
