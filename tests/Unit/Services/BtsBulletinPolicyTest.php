<?php

namespace Tests\Unit\Services;

use App\Services\BtsBulletinPolicy;
use PHPUnit\Framework\TestCase;

class BtsBulletinPolicyTest extends TestCase
{
    public function test_bts1_uses_its_own_annual_weights_when_configured(): void
    {
        $weights = BtsBulletinPolicy::annualWeights(
            true,
            1,
            [
                'bulletin_bts1_semester1_weight' => '1',
                'bulletin_bts1_semester2_weight' => '2',
            ],
            ['semester1' => 1.0, 'semester2' => 1.0]
        );

        self::assertSame(['semester1' => 1.0, 'semester2' => 2.0], $weights);
    }

    public function test_non_bts_classes_keep_the_tenant_default_weights(): void
    {
        $fallback = ['semester1' => 2.0, 'semester2' => 1.0];

        self::assertSame($fallback, BtsBulletinPolicy::annualWeights(false, 1, [], $fallback));
    }

    public function test_bts1_council_decision_is_threshold_based_only_for_second_semester(): void
    {
        $settings = [
            'bulletin_bts1_council_mode' => 'threshold',
            'bulletin_bts1_council_threshold' => '10',
            'bulletin_bts1_council_below_text' => 'Redouble la classe',
            'bulletin_bts1_council_at_or_above_text' => 'Admis(e) en 2e Année BTS',
        ];

        self::assertSame('Admis(e) en 2e Année BTS', BtsBulletinPolicy::councilDecision(true, 1, 'semestre2', 10.0, $settings));
        self::assertSame('Redouble la classe', BtsBulletinPolicy::councilDecision(true, 1, 'semestre2', 9.99, $settings));
        self::assertNull(BtsBulletinPolicy::councilDecision(true, 1, 'semestre1', 14.0, $settings));
    }

    public function test_bts2_can_use_a_fixed_decision_without_a_threshold(): void
    {
        $settings = [
            'bulletin_bts2_council_mode' => 'fixed',
            'bulletin_bts2_council_fixed_text' => "Redouble en cas d'échec à l'examen du BTS",
        ];

        self::assertSame(
            "Redouble en cas d'échec à l'examen du BTS",
            BtsBulletinPolicy::councilDecision(true, 2, 'semestre2', null, $settings)
        );
    }

    public function test_manual_mode_leaves_the_decision_empty_for_a_human_council(): void
    {
        self::assertNull(BtsBulletinPolicy::councilDecision(true, 1, 'semestre2', 14.0, [
            'bulletin_bts1_council_mode' => 'manual',
        ]));
    }

    public function test_bts_council_policy_applies_only_to_second_semester_bts_levels(): void
    {
        self::assertTrue(BtsBulletinPolicy::usesCouncilPolicy(true, 1, 'semestre2'));
        self::assertTrue(BtsBulletinPolicy::usesCouncilPolicy(true, 2, 'semestre2'));
        self::assertFalse(BtsBulletinPolicy::usesCouncilPolicy(true, 1, 'semestre1'));
        self::assertFalse(BtsBulletinPolicy::usesCouncilPolicy(false, 1, 'semestre2'));
        self::assertFalse(BtsBulletinPolicy::usesCouncilPolicy(true, 3, 'semestre2'));
    }

    public function test_manual_bts_second_semester_decision_does_not_fallback_to_stored_text(): void
    {
        self::assertSame(
            '',
            BtsBulletinPolicy::displayCouncilDecision(true, 1, 'semestre2', null, 'Ancienne decision')
        );
    }

    public function test_configured_bts_second_semester_decision_has_priority_over_stored_text(): void
    {
        self::assertSame(
            'Redouble la classe',
            BtsBulletinPolicy::displayCouncilDecision(true, 1, 'semestre2', 'Redouble la classe', 'Ancienne decision')
        );
    }

    public function test_non_bts_or_other_periods_keep_stored_decision_fallback(): void
    {
        self::assertSame(
            'Decision saisie',
            BtsBulletinPolicy::displayCouncilDecision(false, 1, 'semestre2', null, ' Decision saisie ')
        );
        self::assertSame(
            'Decision saisie',
            BtsBulletinPolicy::displayCouncilDecision(true, 1, 'semestre1', null, 'Decision saisie')
        );
    }

    public function test_tenant_can_choose_between_second_semester_and_annual_average_for_a_threshold(): void
    {
        self::assertSame(12.0, BtsBulletinPolicy::decisionAverage('semestre2', 12.0, 9.5));
        self::assertSame(9.5, BtsBulletinPolicy::decisionAverage('annual', 12.0, 9.5));
    }
}
