<?php

namespace Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\Algorithms\LogisticScoring;
use PHPUnit\Framework\TestCase;

class LogisticScoringTest extends TestCase
{
    public function test_sigmoid_of_zero_is_half(): void
    {
        $this->assertEqualsWithDelta(0.5, LogisticScoring::sigmoid(0.0), 0.0001);
    }

    public function test_sigmoid_saturates_high(): void
    {
        $this->assertSame(1.0, LogisticScoring::sigmoid(40.0));
        $this->assertSame(1.0, LogisticScoring::sigmoid(100.0));
    }

    public function test_sigmoid_saturates_low(): void
    {
        $this->assertSame(0.0, LogisticScoring::sigmoid(-40.0));
        $this->assertSame(0.0, LogisticScoring::sigmoid(-100.0));
    }

    public function test_sigmoid_is_monotonic(): void
    {
        $a = LogisticScoring::sigmoid(0.5);
        $b = LogisticScoring::sigmoid(1.0);
        $c = LogisticScoring::sigmoid(2.0);
        $this->assertLessThan($b, $a);
        $this->assertLessThan($c, $b);
    }

    public function test_score_with_no_features_returns_sigmoid_of_bias(): void
    {
        $score = LogisticScoring::score([], [], -2.0);
        $this->assertEqualsWithDelta(LogisticScoring::sigmoid(-2.0), $score, 0.0001);
    }

    public function test_score_ignores_features_without_weights(): void
    {
        $score = LogisticScoring::score(['a' => 1.0, 'b' => 1.0], ['a' => 2.0], 0.0);
        $this->assertEqualsWithDelta(LogisticScoring::sigmoid(2.0), $score, 0.0001);
    }

    public function test_score_combines_weights_and_features(): void
    {
        $score = LogisticScoring::score(
            ['x1' => 0.5, 'x2' => 1.0],
            ['x1' => 4.0, 'x2' => 2.0],
            -3.0,
        );
        $this->assertEqualsWithDelta(LogisticScoring::sigmoid(0.5 * 4.0 + 1.0 * 2.0 - 3.0), $score, 0.0001);
    }

    public function test_risk_label_classifies_high(): void
    {
        $this->assertSame('haut', LogisticScoring::riskLabel(0.95));
        $this->assertSame('haut', LogisticScoring::riskLabel(0.66));
    }

    public function test_risk_label_classifies_medium(): void
    {
        $this->assertSame('moyen', LogisticScoring::riskLabel(0.5));
        $this->assertSame('moyen', LogisticScoring::riskLabel(0.33));
    }

    public function test_risk_label_classifies_low(): void
    {
        $this->assertSame('bas', LogisticScoring::riskLabel(0.0));
        $this->assertSame('bas', LogisticScoring::riskLabel(0.32));
    }

    public function test_risk_label_respects_custom_thresholds(): void
    {
        $this->assertSame('haut', LogisticScoring::riskLabel(0.6, 0.5, 0.2));
        $this->assertSame('moyen', LogisticScoring::riskLabel(0.3, 0.5, 0.2));
        $this->assertSame('bas', LogisticScoring::riskLabel(0.1, 0.5, 0.2));
    }
}
