<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Contracts\AcademicSystemMetricsProvider;
use App\Domain\AcademicPilotage\DTO\AcademicMetricSet;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class AcademicMetricsProviderResolver implements AcademicSystemMetricsProvider
{
    public function __construct(
        private readonly BtsAcademicMetricsProvider $bts,
        private readonly LmdAcademicMetricsProvider $lmd,
        private readonly AcademicPeriodNormalizer $periods,
        private readonly AcademicSystemNormalizer $systems,
    ) {}

    public function metricsFor(StudentMetricContext $context): AcademicMetricSet
    {
        $academicSystem = $this->authoritativeSystem($context->classId);
        if ($this->systems->normalize($context->academicSystem) !== $academicSystem) {
            throw new InvalidArgumentException(
                'Le système académique demandé ne correspond pas à celui de la classe.',
            );
        }

        $canonical = new StudentMetricContext(
            $context->studentId,
            $context->classId,
            $context->academicYearId,
            $academicSystem,
            $this->periods->normalize($context->period),
        );

        return match ($canonical->academicSystem) {
            'BTS' => $this->bts->metricsFor($canonical),
            'LMD' => $this->lmd->metricsFor($canonical),
            default => throw new InvalidArgumentException(
                "Système académique non pris en charge : {$canonical->academicSystem}.",
            ),
        };
    }

    private function authoritativeSystem(int $classId): string
    {
        $class = DB::table('esbtp_classes')
            ->where('id', $classId)
            ->first(['id', 'systeme_academique']);

        if ($class === null) {
            throw new InvalidArgumentException("La classe demandée n'existe pas.");
        }

        return $this->systems->normalize($class->systeme_academique);
    }
}
