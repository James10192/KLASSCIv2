<?php

namespace App\Services\Scoring\Calculators;

use App\Models\User;
use App\Services\Scoring\Concerns\ScoresDatabaseActivity;
use App\Services\Scoring\PersonnelAcademicObligationMetricsService;
use App\Services\Scoring\PersonnelScoreResult;
use Carbon\CarbonInterface;

class AcademicCoordinatorScoringCalculator
{
    use ScoresDatabaseActivity;

    public function __construct(
        private readonly PersonnelAcademicObligationMetricsService $obligationMetrics
    ) {}

    public function calculate(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        return match ($dimension) {
            'academic_coordination' => $this->academic($dimension, $definition, $user, $start, $end),
            'attendance_supervision' => $this->attendanceSupervision($dimension, $definition, $user, $start, $end),
            'academic_workflow' => $this->academicWorkflow($dimension, $definition, $user, $start, $end),
            default => new PersonnelScoreResult($dimension, $definition['label'], 0, $definition['weight'] ?? 0),
        };
    }

    private function academicWorkflow(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $obligation = $this->obligationMetrics->delegatedEntryObligation($user->id, $start, $end);

        return new PersonnelScoreResult(
            dimension: $dimension,
            label: $definition['label'],
            score: $obligation['score'],
            weight: $definition['weight'],
            metrics: [
                'fiches_saisies' => $obligation['numerator'],
                'fiches_recues_assignees' => $obligation['denominator'],
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

    private function academic(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $evaluations = $this->countRows('esbtp_evaluations', $start, $end, function ($query) use ($user) {
            $this->whereActorColumns($query, 'esbtp_evaluations', $user->id);
        });
        $planning = $this->countRows('esbtp_seance_cours', $start, $end, function ($query) use ($user) {
            if ($this->columnExists('esbtp_seance_cours', 'created_by')) {
                $query->where('created_by', $user->id);
            } elseif ($this->columnExists('esbtp_seance_cours', 'updated_by')) {
                $query->where('updated_by', $user->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        });
        $matieres = $this->countRows('esbtp_matieres', $start, $end, function ($query) use ($user) {
            $this->whereActorColumns($query, 'esbtp_matieres', $user->id);
        });

        $score = min(100, $this->scoreByTarget($evaluations, 4) * 0.45 + $this->scoreByTarget($planning, 10) * 0.35 + $this->scoreByTarget($matieres, 2) * 0.2);

        return new PersonnelScoreResult($dimension, $definition['label'], (int) round($score), $definition['weight'], [
            'evaluations_suivies' => $evaluations,
            'actions_planning' => $planning,
            'matieres_suivies' => $matieres,
        ]);
    }

    private function attendanceSupervision(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $attendanceActions = $this->countRows('esbtp_attendances', $start, $end, function ($query) use ($user) {
            $this->whereActorColumns($query, 'esbtp_attendances', $user->id);
        });

        $audits = $this->countRows('audits', $start, $end, fn ($query) => $query
            ->where('user_id', $user->id)
            ->where('auditable_type', 'like', '%Attendance%'));

        $score = min(100, $this->scoreByTarget($attendanceActions, 10) * 0.6 + $this->scoreByTarget($audits, 8) * 0.4);

        return new PersonnelScoreResult($dimension, $definition['label'], (int) round($score), $definition['weight'], [
            'presences_traitees' => $attendanceActions,
            'actions_auditees' => $audits,
        ]);
    }
}
