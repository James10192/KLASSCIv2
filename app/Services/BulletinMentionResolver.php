<?php

namespace App\Services;

use App\Helpers\SettingsHelper;

class BulletinMentionResolver
{
    public const SETTING_KEY = 'bulletin_mention_rules';

    public const AUTH_TEXT_KEY = 'bulletin_authenticity_text';

    public const AUTH_TEXT_DEFAULT = 'Ce document ne peut faire l\'objet d\'aucun duplicata';

    public const EDITION_LABEL_KEY = 'bulletin_edition_label';

    public const EDITION_LABEL_DEFAULT = 'Édition du :';

    public const FAIT_A_KEY = 'bulletin_fait_a';

    public const FAIT_LE_MODE_KEY = 'bulletin_fait_le_mode';

    public const FAIT_LE_DATE_KEY = 'bulletin_fait_le_date';

    public const SOURCE_AVERAGE = 'moyenne';

    public const SOURCE_CONDUCT = 'conduite';

    /**
     * @return list<array{key: string, label: string, min: ?float, max: ?float, source: string, enabled: bool}>
     */
    public static function catalog(): array
    {
        return [
            self::definition('felicitation', 'Félicitation', 16.0, null, self::SOURCE_AVERAGE),
            self::definition('encouragement', 'Encouragement', 13.0, 16.0, self::SOURCE_AVERAGE),
            self::definition('honor_roll', "Tableau d'honneur", 12.0, null, self::SOURCE_AVERAGE),
            self::definition('work_warning', 'Avertissement (Travail)', 3.99, 6.0, self::SOURCE_AVERAGE),
            self::definition('conduct_blame', 'Blâme (Conduite)', 0.0, 3.99, self::SOURCE_CONDUCT),
        ];
    }

    public static function authenticityText(): string
    {
        $text = SettingsHelper::get(self::AUTH_TEXT_KEY, self::AUTH_TEXT_DEFAULT);

        return is_string($text) && trim($text) !== '' ? trim($text) : self::AUTH_TEXT_DEFAULT;
    }

    public static function editionLabel(): string
    {
        $label = SettingsHelper::get(self::EDITION_LABEL_KEY, self::EDITION_LABEL_DEFAULT);

        return is_string($label) && trim($label) !== '' ? trim($label) : self::EDITION_LABEL_DEFAULT;
    }

    public static function editionLine(?string $editionDate): string
    {
        $date = trim((string) $editionDate);
        if ($date === '') {
            return '';
        }

        return self::editionLabel().' '.$date;
    }

    public static function faitA(): string
    {
        $value = SettingsHelper::get(self::FAIT_A_KEY, '');

        return is_string($value) ? trim($value) : '';
    }

    public static function faitLe(?string $editionDate): string
    {
        $mode = (string) SettingsHelper::get(self::FAIT_LE_MODE_KEY, 'edition');

        if ($mode === 'empty') {
            return '';
        }

        if ($mode === 'custom') {
            $custom = SettingsHelper::get(self::FAIT_LE_DATE_KEY, '');

            return is_string($custom) ? trim($custom) : '';
        }

        return trim((string) $editionDate);
    }

    public static function faitALeLine(?string $editionDate): string
    {
        $place = self::faitA();
        $date = self::faitLe($editionDate);

        if ($place === '' && $date === '') {
            return '';
        }

        if ($place !== '' && $date !== '') {
            return 'Fait à '.$place.' le '.$date;
        }

        if ($place !== '') {
            return 'Fait à '.$place;
        }

        return 'Fait le '.$date;
    }

    /**
     * @return list<array{key: string, label: string, min: ?float, max: ?float, source: string, enabled: bool}>
     */
    public static function loadRules(): array
    {
        $raw = SettingsHelper::get(self::SETTING_KEY, null);
        if ($raw === null || $raw === '') {
            return self::catalog();
        }

        return self::normalize(self::decode($raw));
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return list<array{key: string, label: string, min: ?float, max: ?float, source: string, enabled: bool}>
     */
    public static function normalize(array $rows): array
    {
        $rules = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '') {
                $key = trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), '_') ?: 'mention';
            }

            $rules[] = [
                'key' => $key,
                'label' => $label,
                'min' => self::optionalFloat($row['min'] ?? null),
                'max' => self::optionalFloat($row['max'] ?? null),
                'source' => (($row['source'] ?? '') === self::SOURCE_CONDUCT) ? self::SOURCE_CONDUCT : self::SOURCE_AVERAGE,
                'enabled' => array_key_exists('enabled', $row) ? self::truthy($row['enabled']) : false,
            ];
        }

        return $rules;
    }

    /**
     * @param  list<array{key: string, label: string, min: ?float, max: ?float, source: string, enabled: bool}>  $rules
     * @return list<array{key: string, label: string, checked: bool}>
     */
    public static function resolve(?float $moyenne, ?float $conduite, array $rules, bool $autoCalculate = true): array
    {
        $items = [];

        foreach ($rules as $rule) {
            if (! ($rule['enabled'] ?? false)) {
                continue;
            }

            $items[] = [
                'key' => $rule['key'],
                'label' => $rule['label'],
                'checked' => $autoCalculate && self::matches($rule, $moyenne, $conduite),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{key: string, label: string, checked: bool}>
     */
    public static function resolveFromSettings(?float $moyenne, ?float $conduite): array
    {
        if ((string) SettingsHelper::get('bulletin_show_mentions', '1') !== '1') {
            return [];
        }

        return self::resolve(
            $moyenne,
            $conduite,
            self::loadRules(),
            (string) SettingsHelper::get('bulletin_auto_calculate_mention', '1') === '1'
        );
    }

    public static function stackedLabels(?float $moyenne, ?float $conduite, ?string $source = null): string
    {
        $rules = self::loadRules();
        if ($source !== null) {
            $rules = array_values(array_filter(
                $rules,
                static fn (array $rule) => $rule['source'] === $source
            ));
        }

        $labels = [];
        foreach (self::resolve($moyenne, $conduite, $rules) as $item) {
            if ($item['checked']) {
                $labels[] = $item['label'];
            }
        }

        return implode(' + ', $labels);
    }

    /**
     * @param  callable(string, mixed): mixed  $get
     * @return list<array{key: string, label: string, min: ?float, max: ?float, source: string, enabled: bool}>
     */
    public static function fromLegacySettings(callable $get): array
    {
        $rules = [];

        foreach (self::catalog() as $rule) {
            $rules[] = [
                'key' => $rule['key'],
                'label' => $rule['label'],
                'min' => self::optionalFloat($get('bulletin_'.$rule['key'].'_threshold', $rule['min'])),
                'max' => self::optionalFloat($get('bulletin_'.$rule['key'].'_threshold_max', $rule['max'])),
                'source' => (($get('bulletin_'.$rule['key'].'_source', $rule['source'])) === self::SOURCE_CONDUCT)
                    ? self::SOURCE_CONDUCT
                    : self::SOURCE_AVERAGE,
                'enabled' => self::truthy($get('bulletin_show_'.$rule['key'], '1')),
            ];
        }

        return $rules;
    }

    /**
     * @param  array{min: ?float, max: ?float, source: string}  $rule
     */
    public static function matches(array $rule, ?float $moyenne, ?float $conduite): bool
    {
        $score = ($rule['source'] ?? self::SOURCE_AVERAGE) === self::SOURCE_CONDUCT ? $conduite : $moyenne;

        if ($score === null) {
            return false;
        }

        if ($rule['min'] !== null && $score < $rule['min']) {
            return false;
        }

        if ($rule['max'] !== null && $score >= $rule['max']) {
            return false;
        }

        return true;
    }

    /**
     * @return array{key: string, label: string, min: ?float, max: ?float, source: string, enabled: bool}
     */
    private static function definition(string $key, string $label, ?float $min, ?float $max, string $source): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'min' => $min,
            'max' => $max,
            'source' => $source,
            'enabled' => true,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function decode(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function optionalFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private static function truthy(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }
}
