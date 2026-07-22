<?php

namespace Tests\Unit\Services;

use App\Services\LMD\LmdAcademicRuleProfile;
use PHPUnit\Framework\TestCase;

class LmdAcademicRuleProfileTest extends TestCase
{
    public function test_canonical_setting_has_priority_over_legacy_alias(): void
    {
        $settings = ['lmd_validation_threshold' => '11', 'lmd_seuil_validation_ecue' => '9'];
        $profile = new LmdAcademicRuleProfile(fn (string $key, mixed $default = null): mixed => $settings[$key] ?? $default);

        $this->assertSame(11.0, $profile->validationThreshold());
    }

    public function test_legacy_setting_is_preserved_during_reconciliation(): void
    {
        $settings = ['lmd_seuil_validation_ecue' => '10.5', 'lmd_compensation_enabled' => '0'];
        $profile = new LmdAcademicRuleProfile(fn (string $key, mixed $default = null): mixed => $settings[$key] ?? $default);

        $this->assertSame(10.5, $profile->validationThreshold());
        $this->assertFalse($profile->interUeCompensationEnabled());
    }

    /** @dataProvider mentionProvider */
    public function test_mentions_use_the_same_profile_for_bulletins_and_jurys(float $average, ?string $expected): void
    {
        $profile = new LmdAcademicRuleProfile(fn (string $key, mixed $default = null): mixed => $default);

        $this->assertSame($expected, $profile->mentionFor($average));
    }

    public static function mentionProvider(): array
    {
        return [
            'insufficient' => [9.99, null],
            'passable' => [10.0, 'passable'],
            'assez bien' => [12.0, 'assez_bien'],
            'bien' => [14.0, 'bien'],
            'très bien' => [16.0, 'tres_bien'],
            'excellent' => [18.0, 'excellent'],
        ];
    }
}

