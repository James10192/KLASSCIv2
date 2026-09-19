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

    public function test_setting_schema_exposes_defaults_validation_rules_and_reading(): void
    {
        $definitions = BtsBulletinPolicy::settingDefinitions();
        $defaults = BtsBulletinPolicy::defaultSettings();
        $rules = BtsBulletinPolicy::validationRules();

        self::assertArrayHasKey('bulletin_bts1_council_mode', $definitions);
        self::assertArrayHasKey('bulletin_bts1_s1_council_title', $definitions);
        self::assertSame('manual', $defaults['bulletin_bts1_council_mode']);
        self::assertSame('Décision du conseil de classe', $defaults['bulletin_bts1_s1_council_title']);
        self::assertSame('1', $defaults['bulletin_bts1_semester1_weight']);
        self::assertSame('2', $defaults['bulletin_bts1_semester2_weight']);
        self::assertContains('required_if:bulletin_bts1_council_mode,threshold', $rules['bulletin_bts1_council_below_text']);
        self::assertContains('required_if:bulletin_bts2_council_mode,fixed', $rules['bulletin_bts2_council_fixed_text']);

        $settings = BtsBulletinPolicy::readSettings(
            fn (string $key, string $default) => $key === 'bulletin_bts1_semester2_weight' ? '2' : $default
        );

        self::assertSame('2', $settings['bulletin_bts1_semester2_weight']);
        self::assertSame("Redouble en cas d'échec à l'examen du BTS", $settings['bulletin_bts2_council_fixed_text']);
    }

    public function test_effective_settings_merge_partial_input_with_existing_settings(): void
    {
        $stored = [
            'bulletin_bts1_council_mode' => 'threshold',
            'bulletin_bts1_council_below_text' => 'Redouble la classe',
            'bulletin_bts1_semester1_weight' => '0',
            'bulletin_bts1_semester2_weight' => '1',
        ];

        $settings = BtsBulletinPolicy::effectiveSettings(
            [
                'bulletin_bts1_council_below_text' => '',
                'bulletin_bts1_semester2_weight' => '0',
            ],
            fn (string $key, string $default) => $stored[$key] ?? $default
        );

        self::assertSame('threshold', $settings['bulletin_bts1_council_mode']);
        self::assertSame('', $settings['bulletin_bts1_council_below_text']);
        self::assertSame('0', $settings['bulletin_bts1_semester1_weight']);
        self::assertSame([1], BtsBulletinPolicy::invalidWeightPairYears($settings));
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

    /**
     * Ce test figeait le defaut, et l'assertion disait laquelle : sur un
     * bulletin de semestre 2 BTS, la decision saisie a la main etait remplacee
     * par une chaine vide des que la politique ne repondait pas. Or elle ne
     * repond pas en mode MANUEL, qui est le defaut livre : generer le bulletin
     * effacait donc la decision du conseil que l'ecole venait d'ecrire.
     */
    public function test_la_decision_saisie_survit_quand_la_politique_ne_repond_pas(): void
    {
        self::assertSame(
            'Ancienne decision',
            BtsBulletinPolicy::displayCouncilDecision(null, 'Ancienne decision')
        );
    }

    public function test_configured_bts_second_semester_decision_has_priority_over_stored_text(): void
    {
        self::assertSame(
            'Redouble la classe',
            BtsBulletinPolicy::displayCouncilDecision('Redouble la classe', 'Ancienne decision')
        );
    }

    public function test_la_decision_stockee_est_nettoyee_de_ses_espaces(): void
    {
        self::assertSame(
            'Decision saisie',
            BtsBulletinPolicy::displayCouncilDecision(null, ' Decision saisie ')
        );
        self::assertNull(BtsBulletinPolicy::displayCouncilDecision(null, '   '));
    }

    public function test_tenant_can_choose_between_second_semester_and_annual_average_for_a_threshold(): void
    {
        self::assertSame(12.0, BtsBulletinPolicy::decisionAverage('semestre2', 12.0, 9.5));
        self::assertSame(9.5, BtsBulletinPolicy::decisionAverage('annual', 12.0, 9.5));
    }

    /**
     * Le passage en 2e annee se decide sur l'annee entiere. Le defaut livre
     * etait « semestre 2 » : un eleve a 11,20 de moyenne annuelle mais 9,40 au
     * second semestre lisait « Redouble la classe » au bas d'un bulletin qui
     * imprimait 11,20 juste au-dessus.
     */
    public function test_la_decision_se_prend_par_defaut_sur_la_moyenne_annuelle(): void
    {
        self::assertSame('annual', BtsBulletinPolicy::defaultFor('bulletin_bts1_council_average_source'));
    }

    public function test_un_reglage_inconnu_n_a_pas_de_defaut(): void
    {
        self::assertNull(BtsBulletinPolicy::defaultFor('bulletin_bts1_inexistant'));
    }
}
