<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\SettingsHelper;
use Closure;
use InvalidArgumentException;
use Throwable;

final class AppreciationScaleService
{
    public const BTS_SETTING_KEY = 'appreciation_scale_bts';
    public const LMD_SETTING_KEY = 'appreciation_scale_lmd';

    private Closure $resolver;

    /** @param null|Closure(string, mixed): mixed $resolver */
    public function __construct(?Closure $resolver = null)
    {
        $this->resolver = $resolver ?? static fn (string $key, mixed $default = null): mixed => SettingsHelper::get($key, $default);
    }

    /** @return list<array{min: float, max: float, label: string, slug: string}> */
    public function scale(string $system = 'bts'): array
    {
        $system = $this->normalizeSystem($system);
        $key = $this->settingKey($system);

        try {
            $raw = ($this->resolver)($key, null);
            if ($raw !== null && $raw !== '') {
                return $this->normalizeScale($raw, $system);
            }
        } catch (Throwable) {
            // Falling back to bundled defaults keeps result/bulletin rendering alive if settings are unavailable.
        }

        if ($system === 'lmd') {
            try {
                return $this->legacyLmdScale();
            } catch (Throwable) {
                // If legacy thresholds are malformed, the bundled defaults still render a valid scale.
            }
        }

        return $this->defaultScale($system);
    }

    public function labelFor(?float $score, string $system = 'bts', string $emptyLabel = 'N/A'): string
    {
        return $this->classificationFor($score, $system, $emptyLabel)['label'];
    }

    /** @return array{label: string, slug: string, min: float|null, max: float|null} */
    public function classificationFor(?float $score, string $system = 'bts', string $emptyLabel = 'N/A'): array
    {
        if ($score === null) {
            return [
                'label' => $emptyLabel,
                'slug' => 'default',
                'min' => null,
                'max' => null,
            ];
        }

        $score = round((float) $score, 2);
        $scale = $this->scale($system);
        $ascending = $scale;
        usort($ascending, static fn (array $a, array $b): int => $a['min'] <=> $b['min']);

        $fallback = $ascending[0] ?? [
            'label' => $emptyLabel,
            'slug' => 'default',
            'min' => null,
            'max' => null,
        ];

        foreach ($ascending as $range) {
            if ($score >= $range['min']) {
                $fallback = $range;
            }

            if ($score >= $range['min'] && $score <= $range['max']) {
                return $range;
            }
        }

        return $fallback;
    }

    /** @return list<array{min: float, max: float, label: string, slug: string}> */
    public function defaultScale(string $system = 'bts'): array
    {
        $system = $this->normalizeSystem($system);

        $ranges = $system === 'lmd'
            ? [
                ['min' => 18.0, 'max' => 20.0, 'label' => 'Excellent'],
                ['min' => 16.0, 'max' => 17.99, 'label' => 'Très Bien'],
                ['min' => 14.0, 'max' => 15.99, 'label' => 'Bien'],
                ['min' => 12.0, 'max' => 13.99, 'label' => 'Assez Bien'],
                ['min' => 10.0, 'max' => 11.99, 'label' => 'Passable'],
                ['min' => 0.0, 'max' => 9.99, 'label' => 'Insuffisant'],
            ]
            : [
                ['min' => 16.0, 'max' => 20.0, 'label' => 'Excellent'],
                ['min' => 14.0, 'max' => 15.99, 'label' => 'Très Bien'],
                ['min' => 12.0, 'max' => 13.99, 'label' => 'Bien'],
                ['min' => 10.0, 'max' => 11.99, 'label' => 'Assez Bien'],
                ['min' => 8.0, 'max' => 9.99, 'label' => 'Passable'],
                ['min' => 0.0, 'max' => 7.99, 'label' => 'Insuffisant'],
            ];

        return array_map(fn (array $range): array => $this->normalizeRange($range), $ranges);
    }

    /** @return list<array{min: float, max: float, label: string, slug: string}> */
    public function normalizeScale(mixed $raw, string $system = 'bts'): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                throw new InvalidArgumentException('Le barème doit être un JSON valide.');
            }
            $raw = $decoded;
        }

        if (is_array($raw) && isset($raw['ranges']) && is_array($raw['ranges'])) {
            $raw = $raw['ranges'];
        }

        if (! is_array($raw)) {
            throw new InvalidArgumentException('Le barème doit contenir une liste de lignes.');
        }

        $ranges = [];
        foreach (array_values($raw) as $index => $range) {
            if (! is_array($range)) {
                throw new InvalidArgumentException('Chaque ligne du barème doit contenir min, max et libellé.');
            }

            $label = trim((string) ($range['label'] ?? ''));
            $min = $range['min'] ?? null;
            $max = $range['max'] ?? null;

            if ($label === '' && ($min === null || $min === '') && ($max === null || $max === '')) {
                continue;
            }

            if ($label === '') {
                throw new InvalidArgumentException('Chaque ligne du barème doit avoir un libellé.');
            }

            if (! is_numeric($min) || ! is_numeric($max)) {
                throw new InvalidArgumentException("Les bornes de la ligne " . ($index + 1) . " doivent être numériques.");
            }

            $ranges[] = $this->normalizeRange([
                'min' => (float) $min,
                'max' => (float) $max,
                'label' => $label,
            ]);
        }

        if ($ranges === []) {
            throw new InvalidArgumentException('Le barème doit contenir au moins une ligne.');
        }

        usort($ranges, static fn (array $a, array $b): int => $a['min'] <=> $b['min']);

        $previous = null;
        foreach ($ranges as $range) {
            if ($range['min'] < 0 || $range['max'] > 20) {
                throw new InvalidArgumentException('Les bornes du barème doivent rester entre 0 et 20.');
            }

            if ($range['min'] > $range['max']) {
                throw new InvalidArgumentException('Une borne minimum ne peut pas dépasser la borne maximum.');
            }

            if ($previous !== null && $range['min'] <= $previous['max']) {
                throw new InvalidArgumentException('Deux lignes du barème se chevauchent. Ajustez les bornes min/max.');
            }

            $previous = $range;
        }

        usort($ranges, static fn (array $a, array $b): int => $b['min'] <=> $a['min']);

        return $ranges;
    }

    /** @return list<array{min: float, max: float, label: string, slug: string, class: string}> */
    public function frontendScale(string $system = 'bts'): array
    {
        return array_map(static fn (array $range): array => [
            'min' => $range['min'],
            'max' => $range['max'],
            'label' => $range['label'],
            'slug' => $range['slug'],
            'class' => $range['slug'],
        ], $this->scale($system));
    }

    public function encode(array $scale): string
    {
        return json_encode(array_values($scale), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function settingKey(string $system): string
    {
        return $this->normalizeSystem($system) === 'lmd'
            ? self::LMD_SETTING_KEY
            : self::BTS_SETTING_KEY;
    }

    private function normalizeRange(array $range): array
    {
        $label = trim((string) $range['label']);

        return [
            'min' => round((float) $range['min'], 2),
            'max' => round((float) $range['max'], 2),
            'label' => $label,
            'slug' => $this->slug($label),
        ];
    }

    /** @return list<array{min: float, max: float, label: string, slug: string}> */
    private function legacyLmdScale(): array
    {
        $passable = (float) ($this->resolver)('lmd_mention_p_threshold', 10);
        $assezBien = (float) ($this->resolver)('lmd_mention_ab_threshold', 12);
        $bien = (float) ($this->resolver)('lmd_mention_b_threshold', 14);
        $tresBien = (float) ($this->resolver)('lmd_mention_tb_threshold', 16);
        $excellent = (float) ($this->resolver)('lmd_mention_excellent_threshold', 18);

        return $this->normalizeScale([
            ['min' => $excellent, 'max' => 20.0, 'label' => 'Excellent'],
            ['min' => $tresBien, 'max' => $excellent - 0.01, 'label' => 'Très Bien'],
            ['min' => $bien, 'max' => $tresBien - 0.01, 'label' => 'Bien'],
            ['min' => $assezBien, 'max' => $bien - 0.01, 'label' => 'Assez Bien'],
            ['min' => $passable, 'max' => $assezBien - 0.01, 'label' => 'Passable'],
            ['min' => 0.0, 'max' => $passable - 0.01, 'label' => 'Insuffisant'],
        ], 'lmd');
    }

    private function normalizeSystem(string $system): string
    {
        return strtolower(trim($system)) === 'lmd' ? 'lmd' : 'bts';
    }

    private function slug(string $label): string
    {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
        $slug = strtolower((string) $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?: 'appreciation';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'appreciation';
    }
}
