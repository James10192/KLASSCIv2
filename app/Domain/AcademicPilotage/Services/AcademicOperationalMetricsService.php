<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\GradeSheet;

final class AcademicOperationalMetricsService
{
    private const STATUS_SCORES = [
        'expected' => 0,
        'submitted' => 20,
        'received' => 35,
        'in_entry' => 50,
        'entered' => 75,
        'controlled' => 90,
        'validated' => 100,
        'rejected' => 0,
        'correction_requested' => 20,
    ];

    public function __construct(private readonly AcademicPeriodNormalizer $periods) {}

    public function forClass(
        int $classId,
        int $academicYearId,
        string $period,
        ?string $academicSystem = null,
    ): array {
        $query = GradeSheet::query()
            ->where('classe_id', $classId)
            ->where('annee_universitaire_id', $academicYearId)
            ->whereIn('semester', $this->periods->databaseVariants($period))
            ->where('status', '!=', GradeSheetStatus::CANCELLED->value);
        if ($academicSystem !== null) {
            $query->where('academic_system', $academicSystem);
        }
        $sheets = $query->get([
            'status', 'entry_mode', 'assigned_processor_id', 'submitted_at',
            'received_at', 'entered_at', 'controlled_at', 'validated_at',
        ]);

        if ($sheets->isEmpty()) {
            return [
                'score' => null,
                'coverage_pct' => 0,
                'metrics' => ['configured_sheets' => 0, 'unassigned_received_sheets' => 0],
                'reasons' => ["Cadre d'évaluation non configuré pour cette classe."],
            ];
        }

        $statusCounts = $sheets->countBy(fn (GradeSheet $sheet): string => $sheet->status->value)->all();
        $score = $sheets->avg(fn (GradeSheet $sheet): int => self::STATUS_SCORES[$sheet->status->value] ?? 0);
        $unassignedReceived = $sheets
            ->filter(fn (GradeSheet $sheet): bool => $sheet->received_at !== null && $sheet->assigned_processor_id === null)
            ->count();

        return [
            'score' => round((float) $score, 2),
            'coverage_pct' => 100,
            'metrics' => [
                'configured_sheets' => $sheets->count(),
                'status_counts' => $statusCounts,
                'paper_sheets' => $sheets->filter(fn (GradeSheet $sheet): bool => $sheet->entry_mode->value === 'paper')->count(),
                'direct_sheets' => $sheets->filter(fn (GradeSheet $sheet): bool => $sheet->entry_mode->value === 'direct')->count(),
                'unassigned_received_sheets' => $unassignedReceived,
            ],
            'reasons' => $unassignedReceived > 0
            ? ['Des fiches reçues ne sont affectées à aucun responsable de saisie.']
                : [],
        ];
    }
}
