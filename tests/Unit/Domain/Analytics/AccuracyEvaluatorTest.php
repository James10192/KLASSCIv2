<?php

namespace Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\AccuracyEvaluator;
use PHPUnit\Framework\TestCase;

class AccuracyEvaluatorTest extends TestCase
{
    public function test_score_perfect_match(): void
    {
        $this->assertSame(1.0, AccuracyEvaluator::score(1000.0, 1000.0));
    }

    public function test_score_both_zero(): void
    {
        $this->assertSame(1.0, AccuracyEvaluator::score(0.0, 0.0));
    }

    public function test_score_half_off(): void
    {
        $this->assertEqualsWithDelta(0.5, AccuracyEvaluator::score(1000.0, 500.0), 0.001);
        $this->assertEqualsWithDelta(0.5, AccuracyEvaluator::score(500.0, 1000.0), 0.001);
    }

    public function test_score_clamped_to_zero(): void
    {
        $this->assertSame(0.0, AccuracyEvaluator::score(0.0, 1000.0));
    }

    public function test_score_within_bounds(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $a = mt_rand(0, 1_000_000);
            $b = mt_rand(0, 1_000_000);
            $score = AccuracyEvaluator::score((float) $a, (float) $b);
            $this->assertGreaterThanOrEqual(0.0, $score);
            $this->assertLessThanOrEqual(1.0, $score);
        }
    }

    public function test_label_excellent(): void
    {
        $this->assertSame(AccuracyEvaluator::LABEL_EXCELLENT, AccuracyEvaluator::labelForScore(0.95));
        $this->assertSame(AccuracyEvaluator::LABEL_EXCELLENT, AccuracyEvaluator::labelForScore(0.85));
    }

    public function test_label_good(): void
    {
        $this->assertSame(AccuracyEvaluator::LABEL_GOOD, AccuracyEvaluator::labelForScore(0.80));
        $this->assertSame(AccuracyEvaluator::LABEL_GOOD, AccuracyEvaluator::labelForScore(0.70));
    }

    public function test_label_watch(): void
    {
        $this->assertSame(AccuracyEvaluator::LABEL_WATCH, AccuracyEvaluator::labelForScore(0.60));
        $this->assertSame(AccuracyEvaluator::LABEL_WATCH, AccuracyEvaluator::labelForScore(0.0));
    }
}
