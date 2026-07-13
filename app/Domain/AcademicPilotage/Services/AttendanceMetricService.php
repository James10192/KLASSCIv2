<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPAttendanceManualHours;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AttendanceMetricService
{
    private const PRESENT_STATUSES = ['present', 'présent', 'retard', 'late', 'delayed'];

    private const ABSENT_STATUSES = [
        'absent', 'absence', 'excuse', 'excusé', 'absent_justifie',
        'absence_justifiee', 'justifie', 'absent_non_justifie',
        'absence_non_justifiee',
    ];

    public function __construct(private readonly AcademicPeriodNormalizer $periods) {}

    public function forStudent(StudentMetricContext $context): AcademicMetricValue
    {
        $manual = ESBTPAttendanceManualHours::query()
            ->where('etudiant_id', $context->studentId)
            ->where('classe_id', $context->classId)
            ->where('annee_universitaire_id', $context->academicYearId)
            ->whereIn('periode', $this->periods->databaseVariants($context->period))
            ->get();

        return $manual->isNotEmpty()
            ? $this->fromManualHours($context, $manual)
            : $this->fromFinalAttendances($context);
    }

    private function fromManualHours(StudentMetricContext $context, Collection $rows): AcademicMetricValue
    {
        $specific = $rows->whereNotNull('matiere_id');
        $selected = $specific->isNotEmpty() ? $specific : $rows->whereNull('matiere_id');
        $present = (float) $selected->sum('heures_presence');
        $absent = (float) $selected->sum(fn ($row): float => (float) $row->heures_absence_justifiees
            + (float) $row->heures_absence_non_justifiees);
        $observed = $present + $absent;
        $expected = $this->expectedPlanningHours($context);

        $evidence = [
            'source' => $specific->isNotEmpty() ? 'manual_subject' : 'manual_global',
            'records_count' => $selected->count(),
            'observed_hours' => round($observed, 2),
        ];
        if ($expected !== null) {
            $evidence['scheduled_hours'] = $expected;
        }

        return $this->ratioMetric($present, $observed, $evidence, $expected);
    }

    private function fromFinalAttendances(StudentMetricContext $context): AcademicMetricValue
    {
        $statusColumn = Schema::hasColumn('esbtp_attendances', 'statut') ? 'statut' : null;
        $statusColumn ??= Schema::hasColumn('esbtp_attendances', 'status') ? 'status' : null;
        if ($statusColumn === null) {
            return AcademicMetricValue::unavailable(
                'attendance',
                'Colonne de statut de presence indisponible.',
                ['source' => 'final_attendance', 'records_count' => 0],
            );
        }

        $rows = ESBTPAttendance::query()
            ->finalOnly()
            ->where('etudiant_id', $context->studentId)
            ->where('classe_id', $context->classId)
            ->where('annee_universitaire_id', $context->academicYearId)
            ->whereIn($statusColumn, array_merge(self::PRESENT_STATUSES, self::ABSENT_STATUSES))
            ->whereHas('seanceCours.emploiTemps', function ($query) use ($context): void {
                $query->where('classe_id', $context->classId)
                    ->where('annee_universitaire_id', $context->academicYearId)
                    ->whereIn('semestre', $this->periods->databaseVariants($context->period));
            })
            ->get([$statusColumn]);
        $present = $rows->whereIn($statusColumn, self::PRESENT_STATUSES)->count();

        return $this->ratioMetric($present, $rows->count(), [
            'source' => 'final_attendance',
            'records_count' => $rows->count(),
        ]);
    }

    private function expectedPlanningHours(StudentMetricContext $context): ?float
    {
        if (! Schema::hasTable('esbtp_planifications_academiques')
            || ! Schema::hasColumn('esbtp_classes', 'filiere_id')
            || ! Schema::hasColumn('esbtp_classes', 'niveau_etude_id')) {
            return null;
        }

        $class = DB::table('esbtp_classes')->where('id', $context->classId)->first([
            'filiere_id',
            'niveau_etude_id',
        ]);
        if (! $class?->filiere_id || ! $class?->niveau_etude_id) {
            return null;
        }

        $query = DB::table('esbtp_planifications_academiques')
            ->where('annee_universitaire_id', $context->academicYearId)
            ->where('filiere_id', $class->filiere_id)
            ->where('niveau_etude_id', $class->niveau_etude_id)
            ->where('is_active', true)
            ->whereNull('deleted_at');
        $semester = $this->periods->semesterNumber($context->period);
        if ($semester !== null) {
            $query->where('semestre', $semester);
        }

        $hours = (float) $query->sum('volume_horaire_total');

        return $hours > 0 ? round($hours, 2) : null;
    }

    private function ratioMetric(
        float $numerator,
        float $denominator,
        array $evidence,
        ?float $expected = null,
    ): AcademicMetricValue {
        if ($denominator <= 0) {
            return AcademicMetricValue::unavailable(
                'attendance',
                'Aucune donnée de présence disponible pour cette période.',
                $evidence,
            );
        }

        $coverage = $expected !== null && $expected > 0
            ? (int) round(min(100, ($denominator / $expected) * 100))
            : 100;

        return new AcademicMetricValue(
            key: 'attendance',
            value: round(($numerator / $denominator) * 100, 2),
            confidencePct: $coverage,
            evidence: $evidence,
            coveragePct: $coverage,
            numerator: $numerator,
            denominator: $denominator,
        );
    }
}
