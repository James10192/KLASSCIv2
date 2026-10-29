<?php

namespace Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\Algorithms\Statistics;
use PHPUnit\Framework\TestCase;

class StatisticsTest extends TestCase
{
    public function test_mean_handles_empty_array(): void
    {
        $this->assertSame(0.0, Statistics::mean([]));
    }

    public function test_mean_computes_average(): void
    {
        $this->assertSame(3.0, Statistics::mean([1, 2, 3, 4, 5]));
    }

    public function test_median_odd_count(): void
    {
        $this->assertSame(3.0, Statistics::median([5, 1, 3, 4, 2]));
    }

    public function test_median_even_count(): void
    {
        $this->assertSame(2.5, Statistics::median([1, 2, 3, 4]));
    }

    public function test_median_handles_empty_array(): void
    {
        $this->assertSame(0.0, Statistics::median([]));
    }

    public function test_standard_deviation_zero_for_constant(): void
    {
        $this->assertSame(0.0, Statistics::standardDeviation([7, 7, 7, 7]));
    }

    public function test_standard_deviation_handles_too_few_values(): void
    {
        $this->assertSame(0.0, Statistics::standardDeviation([42]));
    }

    public function test_standard_deviation_known_sample(): void
    {
        // Population std dev of [2, 4, 4, 4, 5, 5, 7, 9] = 2.0
        $this->assertEqualsWithDelta(2.0, Statistics::standardDeviation([2, 4, 4, 4, 5, 5, 7, 9]), 0.001);
    }

    public function test_z_score_returns_zero_for_zero_variance(): void
    {
        $this->assertSame(0.0, Statistics::zScore(10, [5, 5, 5]));
    }

    public function test_z_score_positive_for_above_mean(): void
    {
        $this->assertGreaterThan(0, Statistics::zScore(100, [10, 20, 30, 40, 50]));
    }
}
