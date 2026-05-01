<?php

namespace Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\Algorithms\LinearRegression;
use PHPUnit\Framework\TestCase;

class LinearRegressionTest extends TestCase
{
    public function test_fit_handles_empty(): void
    {
        $fit = LinearRegression::fit([]);
        $this->assertSame(0.0, $fit['slope']);
        $this->assertSame(0.0, $fit['intercept']);
    }

    public function test_fit_perfect_line(): void
    {
        $fit = LinearRegression::fit([1, 2, 3, 4, 5]);
        $this->assertEqualsWithDelta(1.0, $fit['slope'], 0.001);
        $this->assertEqualsWithDelta(0.0, $fit['intercept'], 0.001);
    }

    public function test_fit_decreasing(): void
    {
        $fit = LinearRegression::fit([10, 8, 6, 4, 2]);
        $this->assertLessThan(0, $fit['slope']);
    }

    public function test_predict_extrapolates_correctly(): void
    {
        $fit = LinearRegression::fit([2, 4, 6, 8]);
        $this->assertEqualsWithDelta(10.0, LinearRegression::predict($fit, 5), 0.001);
        $this->assertEqualsWithDelta(20.0, LinearRegression::predict($fit, 10), 0.001);
    }
}
