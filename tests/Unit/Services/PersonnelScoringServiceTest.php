<?php

namespace Tests\Unit\Services;

use App\Services\PermissionRegistry;
use App\Services\Scoring\Calculators\AcademicCoordinatorScoringCalculator;
use App\Services\Scoring\Calculators\AdministrativeScoringCalculator;
use App\Services\Scoring\Calculators\CashierScoringCalculator;
use App\Services\Scoring\Calculators\FinanceScoringCalculator;
use App\Services\Scoring\Calculators\TeacherScoringCalculator;
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
    }

    private function service(): PersonnelScoringService
    {
        return new PersonnelScoringService(
            app(PermissionRegistry::class),
            new TeacherScoringCalculator(),
            new FinanceScoringCalculator(),
            new CashierScoringCalculator(),
            new AcademicCoordinatorScoringCalculator(),
            new AdministrativeScoringCalculator()
        );
    }
}
