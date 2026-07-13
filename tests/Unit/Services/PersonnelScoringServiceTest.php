<?php

namespace Tests\Unit\Services;

use App\Services\PermissionRegistry;
use App\Services\Scoring\Calculators\AcademicCoordinatorScoringCalculator;
use App\Services\Scoring\Calculators\AdministrativeScoringCalculator;
use App\Services\Scoring\Calculators\CashierScoringCalculator;
use App\Services\Scoring\Calculators\FinanceScoringCalculator;
use App\Services\Scoring\Calculators\TeacherScoringCalculator;
use App\Services\Scoring\PersonnelAcademicObligationMetricsService;
use App\Services\Scoring\PersonnelScoreResult;
use App\Services\Scoring\PersonnelScoringService;
use ReflectionMethod;
use Tests\TestCase;

class PersonnelScoringServiceTest extends TestCase
{
    public function test_dimension_without_matching_permission_is_excluded(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'dimensionApplies');
        $method->setAccessible(true);

        $dimension = [
            'permissions' => ['paiements.validate'],
        ];

        $this->assertFalse($method->invoke($service, $dimension, ['paiements.create']));
        $this->assertTrue($method->invoke($service, $dimension, ['paiements.validate']));
    }

    public function test_score_levels_are_resolved_from_configuration(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'levelForScore');
        $method->setAccessible(true);

        $this->assertSame('excellent', $method->invoke($service, 90));
        $this->assertSame('good', $method->invoke($service, 75));
        $this->assertSame('watch', $method->invoke($service, 55));
        $this->assertSame('critical', $method->invoke($service, 20));
        $this->assertSame('critical', $method->invoke($service, 0));
    }

    public function test_insufficient_data_requires_no_applicable_weight(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'levelForWeightedScore');
        $method->setAccessible(true);

        $this->assertSame('critical', $method->invoke($service, 0, 10));
        $this->assertSame('insufficient_data', $method->invoke($service, 0, 0));
    }

    public function test_invalid_period_defaults_to_month(): void
    {
        $service = $this->service();

        $this->assertSame('month', $service->normalizePeriodType('unexpected'));
        $this->assertSame('quarter', $service->normalizePeriodType('quarter'));
    }

    public function test_evidence_uses_the_same_weights_as_the_total_score(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'aggregateEvidence');
        $method->setAccessible(true);
        $results = [
            'legacy' => (new PersonnelScoreResult('legacy', 'Historique', 80, 80))->toArray(),
            'obligation' => (new PersonnelScoreResult(
                'obligation',
                'Obligation',
                50,
                20,
                state: PersonnelScoreResult::STATE_MEASURABLE,
                numerator: 1,
                denominator: 2,
                coverage: 0.5,
                confidence: 0.25,
                evidenceHash: str_repeat('a', 64),
            ))->toArray(),
        ];

        $evidence = $method->invoke($service, $results);

        $this->assertSame(0.5, $evidence['coverage']);
        $this->assertSame(0.25, $evidence['confidence']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $evidence['evidence_hash']);
    }

    public function test_no_weighted_dimension_has_no_aggregate_evidence(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'aggregateEvidence');
        $method->setAccessible(true);

        $this->assertSame(
            ['coverage' => null, 'confidence' => null, 'evidence_hash' => null],
            $method->invoke($service, [])
        );
    }

    private function service(): PersonnelScoringService
    {
        $obligationMetrics = new PersonnelAcademicObligationMetricsService;

        return new PersonnelScoringService(
            app(PermissionRegistry::class),
            new TeacherScoringCalculator($obligationMetrics),
            new FinanceScoringCalculator,
            new CashierScoringCalculator,
            new AcademicCoordinatorScoringCalculator($obligationMetrics),
            new AdministrativeScoringCalculator
        );
    }
}
