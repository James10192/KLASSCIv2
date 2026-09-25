<?php

namespace Tests\Unit\Analytics;

use App\Domain\Analytics\Quality\PaymentQualityEvaluator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class PaymentQualityEvaluatorTest extends TestCase
{
    private PaymentQualityEvaluator $evaluator;
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new PaymentQualityEvaluator();
        $this->now = CarbonImmutable::parse('2026-09-24 10:00:00');
    }

    /** @test */
    public function regular_daily_entry_is_reliable(): void
    {
        $groups = [];
        foreach (range(1, 20) as $day) {
            $groups[] = ['jour' => sprintf('2026-09-%02d', $day), 'user_id' => 5, 'nombre' => 12, 'anciens' => 1];
        }

        $result = $this->evaluator->evaluate($groups, $this->now->subDays(2), $this->now, PaymentQualityEvaluator::DEFAULTS);

        $this->assertSame(PaymentQualityEvaluator::FIABLE, $result['niveau']);
        $this->assertSame([], $result['constats']);
        $this->assertSame(240, $result['mesures']['paiements_analyses']);
    }

    /** @test */
    public function yakro_shape_is_flagged_as_catch_up_and_stale(): void
    {
        // Forme observee sur une ecole reelle : toute l'annee saisie en quelques
        // journees d'avril a juin par un meme compte, puis plus rien.
        $groups = [
            ['jour' => '2026-04-14', 'user_id' => 6, 'nombre' => 380, 'anciens' => 360],
            ['jour' => '2026-05-06', 'user_id' => 5, 'nombre' => 640, 'anciens' => 600],
            ['jour' => '2026-05-07', 'user_id' => 5, 'nombre' => 230, 'anciens' => 215],
            ['jour' => '2026-06-10', 'user_id' => 3, 'nombre' => 12, 'anciens' => 0],
        ];

        $result = $this->evaluator->evaluate($groups, CarbonImmutable::parse('2026-07-31 16:00:00'), $this->now, PaymentQualityEvaluator::DEFAULTS);

        $this->assertSame(PaymentQualityEvaluator::A_VERIFIER, $result['niveau']);
        $this->assertSame(['donnees_perimees', 'saisie_rattrapage'], array_column($result['constats'], 'code'));
        $this->assertSame(1250, $result['mesures']['paiements_rattrapage']);
        $this->assertSame(55, $result['mesures']['jours_depuis_derniere_saisie']);
        $this->assertStringContainsString('3 journées', $result['constats'][1]['detail']);
    }

    /** @test */
    public function a_busy_cash_day_at_the_start_of_the_year_is_not_a_catch_up(): void
    {
        // Beaucoup de paiements le meme jour, mais tous du jour : c'est une
        // vraie caisse de rentree, pas un rattrapage.
        $groups = [['jour' => '2026-09-15', 'user_id' => 7, 'nombre' => 300, 'anciens' => 4]];

        $result = $this->evaluator->evaluate($groups, $this->now->subDay(), $this->now, PaymentQualityEvaluator::DEFAULTS);

        $this->assertSame(0, $result['mesures']['paiements_rattrapage']);
        $this->assertSame(PaymentQualityEvaluator::FIABLE, $result['niveau']);
    }

    /** @test */
    public function old_payments_entered_one_by_one_are_not_a_catch_up(): void
    {
        $groups = [['jour' => '2026-09-10', 'user_id' => 7, 'nombre' => 35, 'anciens' => 35]];

        $result = $this->evaluator->evaluate($groups, $this->now->subDays(3), $this->now, PaymentQualityEvaluator::DEFAULTS);

        $this->assertSame(0, $result['mesures']['paiements_rattrapage'], 'moins de 40 saisies dans la journee');
    }

    /** @test */
    public function too_few_payments_is_insufficient(): void
    {
        $groups = [['jour' => '2026-09-20', 'user_id' => 7, 'nombre' => 8, 'anciens' => 0]];

        $result = $this->evaluator->evaluate($groups, $this->now->subDays(4), $this->now, PaymentQualityEvaluator::DEFAULTS);

        $this->assertSame(PaymentQualityEvaluator::INSUFFISANT, $result['niveau']);
        $this->assertSame(['echantillon_insuffisant'], array_column($result['constats'], 'code'));
    }

    /** @test */
    public function no_payment_at_all_says_so_instead_of_staying_silent(): void
    {
        $result = $this->evaluator->evaluate([], null, $this->now, PaymentQualityEvaluator::DEFAULTS);

        $this->assertSame(PaymentQualityEvaluator::INSUFFISANT, $result['niveau']);
        $this->assertSame(['aucune_saisie'], array_column($result['constats'], 'code'));
        $this->assertNull($result['mesures']['jours_depuis_derniere_saisie']);
    }

    /** @test */
    public function thresholds_come_from_the_school_settings(): void
    {
        $groups = [['jour' => '2026-09-01', 'user_id' => 5, 'nombre' => 50, 'anciens' => 0]];
        $seuils = ['stale_days' => 10] + PaymentQualityEvaluator::DEFAULTS;

        $result = $this->evaluator->evaluate($groups, $this->now->subDays(15), $this->now, $seuils);

        $this->assertSame(['donnees_perimees'], array_column($result['constats'], 'code'));
    }
}
