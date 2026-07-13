<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Contracts\AcademicSystemMetricsProvider;
use App\Domain\AcademicPilotage\DTO\AcademicMetricSet;
use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Services\StudentAcademicHealthService;
use Tests\TestCase;

class StudentAcademicHealthServiceTest extends TestCase
{
    public function test_weights_are_loaded_from_config(): void
    {
        $this->assertSame([
            'academic_performance' => 30,
            'assessment_completion' => 25,
            'attendance' => 20,
            'progression' => 15,
            'open_alerts' => 10,
        ], config('academic_pilotage.student_health.weights'));
    }

    public function test_it_normalizes_the_score_over_available_factors(): void
    {
        $result = (new StudentAcademicHealthService)->calculate(new AcademicMetricSet([
            $this->metric('academic_performance', 80, 100),
            $this->metric('assessment_completion', 100, 50),
            $this->metric('attendance', 60, 80),
        ]));

        $this->assertSame(75, $result->coveragePct);
        $this->assertSame(59, $result->confidencePct);
        $this->assertSame(81.33, $result->score);
        $this->assertSame('healthy', $result->level);
        $this->assertTrue($result->hasSufficientData());
    }

    public function test_it_returns_insufficient_data_below_sixty_percent_coverage(): void
    {
        $result = (new StudentAcademicHealthService)->calculate(new AcademicMetricSet([
            $this->metric('academic_performance', 100, 100),
            $this->metric('assessment_completion', 100, 100),
        ]));

        $this->assertSame(55, $result->coveragePct);
        $this->assertSame(55, $result->confidencePct);
        $this->assertNull($result->score);
        $this->assertSame('insufficient_data', $result->level);
        $this->assertStringContainsString('Données insuffisantes', $result->explanations[0]);
    }

    public function test_sixty_percent_coverage_is_sufficient(): void
    {
        $result = (new StudentAcademicHealthService)->calculate(new AcademicMetricSet([
            $this->metric('assessment_completion', 70, 100),
            $this->metric('attendance', 70, 100),
            $this->metric('progression', 70, 100),
        ]));

        $this->assertSame(60, $result->coveragePct);
        $this->assertSame(60, $result->confidencePct);
        $this->assertSame(70.0, $result->score);
        $this->assertSame('watch', $result->level);
    }

    public function test_evaluate_uses_the_metrics_provider_contract(): void
    {
        $metrics = new AcademicMetricSet([
            $this->metric('academic_performance', 90, 100),
            $this->metric('attendance', 90, 100),
            $this->metric('assessment_completion', 90, 100),
            $this->metric('progression', 90, 100),
            $this->metric('open_alerts', 90, 100),
        ]);
        $provider = new class($metrics) implements AcademicSystemMetricsProvider
        {
            public function __construct(private readonly AcademicMetricSet $metrics) {}

            public function metricsFor(StudentMetricContext $context): AcademicMetricSet
            {
                return $this->metrics;
            }
        };
        $context = new StudentMetricContext(1, 2, 3, 'LMD', 'semestre1');

        $result = (new StudentAcademicHealthService($provider))->evaluate($context);

        $this->assertSame(90.0, $result->score);
        $this->assertSame('healthy', $result->level);
        $this->assertStringNotContainsString("\u{00C3}", implode(' ', $result->explanations));
    }

    private function metric(string $key, float $value, int $confidence): AcademicMetricValue
    {
        return new AcademicMetricValue($key, $value, $confidence, ['sample_count' => 10]);
    }
}
