<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class AppreciationScaleSettingsService
{
    public function __construct(private readonly AppreciationScaleService $scaleService)
    {
    }

    public function ensureDefaults(): void
    {
        foreach (['bts', 'lmd'] as $system) {
            $key = $this->scaleService->settingKey($system);
            $label = strtoupper($system);
            $defaultScale = $this->scaleService->scale($system);

            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $this->scaleService->encode($defaultScale),
                    'type' => 'string',
                    'group' => $system === 'lmd' ? 'lmd' : 'bulletin',
                    'category' => $system === 'lmd' ? 'lmd' : 'bulletin',
                    'description' => "Barème configurable des appréciations {$label} sur 20",
                    'is_required' => false,
                    'default_value' => $this->scaleService->encode($defaultScale),
                    'validation_rules' => null,
                    'sort_order' => $system === 'lmd' ? 72 : 157,
                ]
            );
        }
    }

    /** @return array{bts: array, lmd: array} */
    public function scales(): array
    {
        return [
            'bts' => $this->scaleService->scale('bts'),
            'lmd' => $this->scaleService->scale('lmd'),
        ];
    }

    public function processForm(Request $request, array &$updatedSettings, array &$errors): void
    {
        $this->processScaleInput($request, 'bts', $updatedSettings, $errors);
        $this->processScaleInput($request, 'lmd', $updatedSettings, $errors);
    }

    public function shouldSkipGenericSetting(Request $request, string $settingKey): bool
    {
        return $request->has('appreciation_scale_lmd')
            && in_array($settingKey, $this->legacyLmdMentionThresholdKeys(), true);
    }

    private function processScaleInput(Request $request, string $system, array &$updatedSettings, array &$errors): void
    {
        $field = 'appreciation_scale_' . $system;
        if (! $request->has($field)) {
            return;
        }

        $key = $this->scaleService->settingKey($system);

        try {
            $scale = $this->scaleService->normalizeScale($request->input($field), $system);
        } catch (InvalidArgumentException $e) {
            $errors[$field] = $e->getMessage();
            return;
        }

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $this->scaleService->encode($scale),
                'type' => 'string',
                'group' => $system === 'lmd' ? 'lmd' : 'bulletin',
                'category' => $system === 'lmd' ? 'lmd' : 'bulletin',
                'description' => 'Barème configurable des appréciations ' . strtoupper($system) . ' sur 20',
                'is_active' => true,
                'is_required' => false,
                'validation_rules' => null,
                'updated_by' => auth()->id(),
            ]
        );

        if ($system === 'lmd') {
            $this->syncLegacyLmdMentionThresholds($scale);
        }

        $updatedSettings[] = $key;
    }

    private function syncLegacyLmdMentionThresholds(array $scale): void
    {
        $bySlug = collect($scale)->keyBy('slug');
        $mapping = [
            'lmd_mention_p_threshold' => 'passable',
            'lmd_mention_ab_threshold' => 'assez-bien',
            'lmd_mention_b_threshold' => 'bien',
            'lmd_mention_tb_threshold' => 'tres-bien',
            'lmd_mention_excellent_threshold' => 'excellent',
        ];

        foreach ($mapping as $key => $slug) {
            $range = $bySlug->get($slug);
            if (! $range) {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $range['min'],
                    'type' => 'float',
                    'group' => 'lmd',
                    'category' => 'lmd',
                    'description' => 'Seuil LMD synchronisé depuis le barème des appréciations',
                    'is_active' => true,
                    'is_required' => false,
                    'validation_rules' => null,
                    'updated_by' => auth()->id(),
                ]
            );
        }
    }

    /** @return list<string> */
    private function legacyLmdMentionThresholdKeys(): array
    {
        return [
            'lmd_mention_p_threshold',
            'lmd_mention_ab_threshold',
            'lmd_mention_b_threshold',
            'lmd_mention_tb_threshold',
            'lmd_mention_excellent_threshold',
        ];
    }
}
