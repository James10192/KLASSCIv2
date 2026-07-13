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
    ) {}

    public function metricsFor(StudentMetricContext $context): AcademicMetricSet
    {
        $academicSystem = $this->authoritativeSystem($context->classId);
        if (strtoupper($context->academicSystem) !== $academicSystem) {
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
        $system = DB::table('esbtp_classes')
            ->where('id', $classId)
            ->value('systeme_academique');
        $system = strtoupper(trim((string) $system));

        if (! in_array($system, ['BTS', 'LMD'], true)) {
            throw new InvalidArgumentException(
                "Le système académique de la classe n'est pas pris en charge.",
            );
        }

        return $system;
    }
}
