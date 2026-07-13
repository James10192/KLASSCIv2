<?php

namespace App\Services\Scoring\Calculators;

use App\Models\ESBTPTeacher;
use App\Models\User;
use App\Services\Scoring\Concerns\ScoresDatabaseActivity;
use App\Services\Scoring\PersonnelAcademicObligationMetricsService;
use App\Services\Scoring\PersonnelScoreResult;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class TeacherScoringCalculator
{
    use ScoresDatabaseActivity;

    public function __construct(
        private readonly PersonnelAcademicObligationMetricsService $obligationMetrics
    ) {}

    public function calculate(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $teacher = $user->teacherProfile ?: ESBTPTeacher::where('user_id', $user->id)->first();
        $teacherId = $teacher?->id;

        return match ($dimension) {
            'teacher_attendance' => $this->attendance($dimension, $definition, $user, $teacherId, $start, $end),
            'teacher_delivery' => $this->delivery($dimension, $definition, $user, $teacherId, $start, $end),
            'grades_activity' => $this->grades($dimension, $definition, $user, $teacherId, $start, $end),
            default => new PersonnelScoreResult($dimension, $definition['label'], 0, $definition['weight'] ?? 0),
        };
    }

    private function attendance(string $dimension, array $definition, User $user, ?int $teacherId, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $planned = $this->countTeacherRows('esbtp_seance_cours', $teacherId, $user, $start, $end, function (Builder $query): void {
            if ($this->columnExists('esbtp_seance_cours', 'date_seance')) {
                $query->whereNotNull('date_seance');
            }
        });
        $signed = $this->countTeacherRows('esbtp_teacher_attendances', $teacherId, $user, $start, $end, function (Builder $query): void {
            if ($this->columnExists('esbtp_teacher_attendances', 'type')) {
                $query->where('type', 'start');
            }
        });
        $late = $this->countTeacherRows('esbtp_teacher_attendances', $teacherId, $user, $start, $end, fn (Builder $query) => $query->where('status', 'late'));
        $absent = $this->countTeacherRows('esbtp_teacher_attendances', $teacherId, $user, $start, $end, fn (Builder $query) => $query->where('status', 'absent'));

        if ($planned === 0) {
            return $this->withoutObligation($dimension, $definition, 'Aucune séance planifiée sur la période.');
        }

        $base = $this->scoreByRate($signed, $planned);
        $score = max(0, $base - ($late * 5) - ($absent * 15));

        return $this->measurableObligation($dimension, $definition, $score, $signed, $planned, [
            'seances_planifiees' => $planned,
            'emargements' => $signed,
            'retards' => $late,
            'absences' => $absent,
        ]);
    }

    private function countTeacherRows(
        string $table,
        ?int $teacherId,
        User $user,
        CarbonInterface $start,
        CarbonInterface $end,
        ?callable $scope = null
    ): int {
        return $this->countRows($table, $start, $end, function (Builder $query) use ($teacherId, $user, $scope): void {
            $query->where(fn (Builder $actor) => $this->whereTeacher($actor, $teacherId, $user->id));
            if ($scope) {
                $scope($query);
            }
        });
    }

    private function whereTeacher(Builder $query, ?int $teacherId, int $userId): void
    {
        $query->where('teacher_id', $teacherId ?: $userId);

        if ($teacherId && $teacherId !== $userId) {
            $query->orWhere('teacher_id', $userId);
        }
    }

    private function delivery(string $dimension, array $definition, User $user, ?int $teacherId, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $planned = $this->plannedSessions($teacherId, $user, $start, $end);
        if ($planned === 0) {
            return $this->withoutObligation($dimension, $definition, 'Aucune séance pédagogique assignée sur la période.');
        }

        $rollCalls = $this->rollCallSessions($teacherId, $user, $start, $end);

        $reports = $this->countRows('esbtp_session_reports', $start, $end, function ($query) use ($teacherId, $user) {
            $query->where(function ($q) use ($teacherId, $user) {
                if ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                }
                $q->orWhere('teacher_id', $user->id);
            });
            if ($this->columnExists('esbtp_session_reports', 'status')) {
                $query->whereIn('status', ['submitted', 'validated', 'completed']);
            }
        });

        $rollCalls = min($rollCalls, $planned);
        $reports = min($reports, $planned);
        $score = min(100, $this->scoreByRate($rollCalls, $planned) * 0.55
            + $this->scoreByRate($reports, $planned) * 0.45);

        return $this->measurableObligation(
            $dimension,
            $definition,
            (int) round($score),
            $rollCalls + $reports,
            $planned * 2,
            [
                'seances_planifiees' => $planned,
                'appels_etudiants' => $rollCalls,
                'rapports_soumis' => $reports,
            ],
        );
    }

    private function grades(string $dimension, array $definition, User $user, ?int $teacherId, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $evaluations = $this->countRows('esbtp_evaluations', $start, $end, function ($query) use ($teacherId, $user) {
            $query->where(function ($q) use ($teacherId, $user) {
                $q->where('created_by', $user->id)->orWhere('enseignant_id', $user->id);
                if ($teacherId) {
                    $q->orWhere('enseignant_id', $teacherId);
                }
            });
        });

        $notes = $this->countRows('esbtp_notes', $start, $end, function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)->orWhere('updated_by', $user->id);
            });
        });

        $obligation = $this->obligationMetrics->teacherGradeObligation($teacherId, $start, $end);

        return new PersonnelScoreResult(
            dimension: $dimension,
            label: $definition['label'],
            score: $obligation['score'],
            weight: $definition['weight'],
            metrics: [
                'evaluations' => $evaluations,
                'notes_saisies' => $notes,
                'obligations_remplies' => $obligation['numerator'],
                'obligations_attendues' => $obligation['denominator'],
            ],
            messages: $obligation['messages'],
            state: $obligation['state'],
            numerator: $obligation['numerator'],
            denominator: $obligation['denominator'],
            coverage: $obligation['coverage'],
            confidence: $obligation['confidence'],
            evidenceHash: $obligation['evidence_hash'],
        );
    }

    private function plannedSessions(?int $teacherId, User $user, CarbonInterface $start, CarbonInterface $end): int
    {
        return $this->countRows('esbtp_seance_cours', $start, $end, function ($query) use ($teacherId, $user) {
            $query->where(function ($actor) use ($teacherId, $user): void {
                if ($teacherId) {
                    $actor->where('teacher_id', $teacherId);
                }
                $actor->orWhere('teacher_id', $user->id);
            });
        });
    }

    private function rollCallSessions(?int $teacherId, User $user, CarbonInterface $start, CarbonInterface $end): int
    {
        if (! $this->columnExists('esbtp_attendances', 'seance_cours_id')) {
            return 0;
        }

        $query = DB::table('esbtp_attendances')
            ->whereBetween('date', [$start, $end])
            ->whereNull('deleted_at');
        if ($this->columnExists('esbtp_attendances', 'teacher_id')) {
            $query->where(function ($actor) use ($teacherId, $user): void {
                if ($teacherId) {
                    $actor->where('teacher_id', $teacherId);
                }
                $actor->orWhere('teacher_id', $user->id);
            });
        } else {
            $query->where('created_by', $user->id);
        }

        return (int) $query->distinct()->count('seance_cours_id');
    }

    private function withoutObligation(string $dimension, array $definition, string $message): PersonnelScoreResult
    {
        return new PersonnelScoreResult(
            dimension: $dimension,
            label: $definition['label'],
            score: 0,
            weight: $definition['weight'],
            messages: [$message],
            state: PersonnelScoreResult::STATE_NON_APPLICABLE,
            numerator: 0,
            denominator: 0,
        );
    }

    private function measurableObligation(
        string $dimension,
        array $definition,
        int $score,
        int $numerator,
        int $denominator,
        array $metrics,
    ): PersonnelScoreResult {
        $evidence = ['numerator' => $numerator, 'denominator' => $denominator, 'metrics' => $metrics];

        return new PersonnelScoreResult(
            dimension: $dimension,
            label: $definition['label'],
            score: $score,
            weight: $definition['weight'],
            metrics: $metrics,
            numerator: $numerator,
            denominator: $denominator,
            coverage: 1.0,
            confidence: round(min(1, $denominator / 5), 4),
            evidenceHash: hash('sha256', json_encode($evidence, JSON_THROW_ON_ERROR)),
        );
    }
}
