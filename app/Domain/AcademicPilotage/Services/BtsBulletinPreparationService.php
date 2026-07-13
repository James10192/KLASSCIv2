<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Contracts\BulletinPreparationService;
use App\Domain\AcademicPilotage\DTO\BulletinPreparationResult;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;

final class BtsBulletinPreparationService implements BulletinPreparationService
{
    public function __construct(private readonly BtsCurrentResultSnapshotService $snapshots) {}

    public function prepare(
        int $studentId,
        int $classId,
        int $academicYearId,
        string $period,
    ): BulletinPreparationResult {
        $snapshot = $this->snapshots->getPeriodeSnapshot($studentId, $classId, $academicYearId, $period);
        $issues = [];
        $warnings = [];

        if (! ($snapshot['configuration']['ready'] ?? false)) {
            $issues[] = $this->issue(
                'missing_configuration',
                'La configuration BTS du bulletin est incomplète.',
                'blocking',
            );
        }

        if (($snapshot['state'] ?? null) === 'no_data') {
            $issues[] = $this->issue('no_grade_data', 'Aucune note exploitable pour cette période.', 'blocking');
        }

        if (($snapshot['state'] ?? null) === 'annual_incomplete') {
            $issues[] = $this->issue(
                'annual_incomplete',
                'Le calcul annuel BTS est partiel, une seule période est disponible.',
                'blocking',
            );
        }

        if (($snapshot['coefficients_missing'] ?? false) === true) {
            $warnings[] = $this->issue(
                'coefficients_missing',
                'Des coefficients sont manquants, la moyenne brute peut être provisoire.',
                'warning',
            );
        }

        return new BulletinPreparationResult(
            system: 'BTS',
            studentId: $studentId,
            classId: $classId,
            academicYearId: $academicYearId,
            period: (string) ($snapshot['periode'] ?? $period),
            ready: $issues === [],
            coveragePct: $issues === [] ? 100 : 0,
            blockingIssues: $issues,
            warnings: $warnings,
            evidence: [
                'state' => $snapshot['state'] ?? null,
                'notes_count' => $snapshot['notes_count'] ?? 0,
                'manual_resultats_count' => $snapshot['manual_resultats_count'] ?? 0,
                'missing_configuration' => $snapshot['configuration']['missing_items'] ?? [],
            ],
        );
    }

    /** @return array{code: string, message: string, severity: string} */
    private function issue(string $code, string $message, string $severity): array
    {
        return compact('code', 'message', 'severity');
    }
}
