<?php

namespace App\Services\Scoring\Calculators;

use App\Models\ESBTPTeacher;
use App\Models\User;
use App\Services\Scoring\Concerns\ScoresDatabaseActivity;
use App\Services\Scoring\PersonnelScoreResult;
use Carbon\CarbonInterface;

class TeacherScoringCalculator
{
    use ScoresDatabaseActivity;

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
        $planned = $this->countRows('esbtp_seance_cours', $start, $end, function ($query) use ($teacherId, $user) {
            $query->where(function ($q) use ($teacherId, $user) {
                if ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                }
                $q->orWhere('teacher_id', $user->id);
            });
            if ($this->columnExists('esbtp_seance_cours', 'date_seance')) {
                $query->whereNotNull('date_seance');
            }
        });

        $signed = $this->countRows('esbtp_teacher_attendances', $start, $end, function ($query) use ($teacherId, $user) {
            $query->where(function ($q) use ($teacherId, $user) {
                if ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                }
                $q->orWhere('teacher_id', $user->id);
            });
            if ($this->columnExists('esbtp_teacher_attendances', 'type')) {
                $query->where('type', 'start');
            }
        });

        $late = $this->countRows('esbtp_teacher_attendances', $start, $end, function ($query) use ($teacherId, $user) {
            $query->where(function ($q) use ($teacherId, $user) {
                if ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                }
                $q->orWhere('teacher_id', $user->id);
            })->where('status', 'late');
        });

        $absent = $this->countRows('esbtp_teacher_attendances', $start, $end, function ($query) use ($teacherId, $user) {
            $query->where(function ($q) use ($teacherId, $user) {
                if ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                }
                $q->orWhere('teacher_id', $user->id);
            })->where('status', 'absent');
        });

        $base = $planned > 0 ? $this->scoreByRate($signed, $planned) : $this->scoreByTarget($signed, 4);
        $score = max(0, $base - ($late * 5) - ($absent * 15));

        return new PersonnelScoreResult($dimension, $definition['label'], $score, $definition['weight'], [
            'seances_planifiees' => $planned,
            'emargements' => $signed,
            'retards' => $late,
            'absences' => $absent,
        ]);
    }

    private function delivery(string $dimension, array $definition, User $user, ?int $teacherId, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $rollCalls = $this->countRows('esbtp_attendances', $start, $end, function ($query) use ($teacherId, $user) {
            if ($this->columnExists('esbtp_attendances', 'teacher_id')) {
                $query->where(function ($q) use ($teacherId, $user) {
                    if ($teacherId) {
                        $q->where('teacher_id', $teacherId);
                    }
                    $q->orWhere('teacher_id', $user->id);
                });
            } elseif ($this->columnExists('esbtp_attendances', 'created_by')) {
                $query->where('created_by', $user->id);
            }
        });

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

        $score = min(100, $this->scoreByTarget($rollCalls, 8) * 0.55 + $this->scoreByTarget($reports, 4) * 0.45);

        return new PersonnelScoreResult($dimension, $definition['label'], (int) round($score), $definition['weight'], [
            'appels_etudiants' => $rollCalls,
            'rapports_soumis' => $reports,
        ]);
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

        $score = min(100, $this->scoreByTarget($evaluations, 2) * 0.35 + $this->scoreByTarget($notes, 20) * 0.65);

        return new PersonnelScoreResult($dimension, $definition['label'], (int) round($score), $definition['weight'], [
            'evaluations' => $evaluations,
            'notes_saisies' => $notes,
        ]);
    }
}
