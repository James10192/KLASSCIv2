<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Contracts;

use App\Domain\AcademicPilotage\DTO\AcademicMetricSet;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;

interface AcademicSystemMetricsProvider
{
    public function metricsFor(StudentMetricContext $context): AcademicMetricSet;
}
