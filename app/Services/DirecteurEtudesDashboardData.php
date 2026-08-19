<?php

namespace App\Services;

use App\Domain\AcademicPilotage\Services\AcademicPilotageSummaryService;
use App\Domain\Students\StudentCountService;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPTeacher;
use Illuminate\Support\Facades\Auth;

class DirecteurEtudesDashboardData
{
    public function build(): array
    {
        $user = Auth::user();
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $data = [
            'user' => $user,
            'anneeEnCours' => $anneeEnCours,
            'totalStudents' => 0,
            'totalStudentsBase' => 0,
            'anneeLabel' => null,
            'totalClasses' => 0,
            'totalTeachers' => 0,
            'totalExamens' => 0,
            'evaluationsSansNotesCount' => 0,
            'totalEmploiTemps' => 0,
            'activeEmploiTemps' => 0,
            'expiredEmploiTemps' => 0,
            'classesWithoutTimetable' => 0,
            'todayAttendances' => 0,
            'attendanceStats' => [
                'total_present' => 0,
                'total_absent' => 0,
                'attendance_rate' => 0,
            ],
            'pendingInscriptionsCount' => 0,
            'unpaidStudentsCount' => 0,
            'academicHealth' => [
                'academic_score' => null,
                'operational_score' => null,
                'open_alerts' => 0,
                'blocking_alerts' => 0,
                'sheets_pending' => 0,
                'bulletin_blockers' => 0,
            ],
        ];

        try {
            $studentCounts = app(StudentCountService::class)->counts();
            $data['totalStudents'] = $studentCounts['inscrits_annee_courante'];
            $data['totalStudentsBase'] = $studentCounts['total_base'];
            $data['anneeLabel'] = $studentCounts['annee_courante_label'];
        } catch (\Throwable $e) {
            // Keep defaults when the academic year or student tables are unavailable.
        }

        try {
            $data['totalClasses'] = ESBTPClasse::query()->where('is_active', true)->count();
            $data['totalTeachers'] = ESBTPTeacher::query()->count();
        } catch (\Throwable $e) {
            // Keep defaults.
        }

        try {
            $evalQuery = ESBTPEvaluation::query();
            if ($anneeEnCours) {
                $evalQuery->whereHas('classe', fn ($q) => $q->where('annee_universitaire_id', $anneeEnCours->id));
            }
            $data['totalExamens'] = (clone $evalQuery)->count();
            $data['evaluationsSansNotesCount'] = (clone $evalQuery)
                ->whereDate('date_evaluation', '<', today())
                ->whereDoesntHave('notes')
                ->count();
        } catch (\Throwable $e) {
            // Keep defaults.
        }

        try {
            $edtQuery = ESBTPEmploiTemps::query();
            if ($anneeEnCours) {
                $edtQuery->where('annee_universitaire_id', $anneeEnCours->id);
            }
            $data['totalEmploiTemps'] = (clone $edtQuery)->count();
            $data['activeEmploiTemps'] = (clone $edtQuery)
                ->where('is_active', true)
                ->whereDate('date_debut', '<=', today())
                ->whereDate('date_fin', '>=', today())
                ->count();
            $data['expiredEmploiTemps'] = (clone $edtQuery)
                ->whereDate('date_fin', '<', today())
                ->count();
            $classesAvecEdt = (clone $edtQuery)->pluck('classe_id')->unique();
            $data['classesWithoutTimetable'] = ESBTPClasse::query()
                ->where('is_active', true)
                ->whereNotIn('id', $classesAvecEdt)
                ->count();
        } catch (\Throwable $e) {
            // Keep defaults.
        }

        try {
            $attendanceQuery = ESBTPAttendance::query()->whereDate('date', today());
            if ($anneeEnCours) {
                $attendanceQuery->whereHas('etudiant.inscriptions', function ($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id)
                        ->where('status', 'active');
                });
            }

            $data['todayAttendances'] = (clone $attendanceQuery)->count();
            $totalPresent = (clone $attendanceQuery)->where('status', 'present')->count();
            $totalAbsent = (clone $attendanceQuery)->where('status', 'absent')->count();
            $data['attendanceStats'] = [
                'total_present' => $totalPresent,
                'total_absent' => $totalAbsent,
                'attendance_rate' => $totalPresent + $totalAbsent > 0
                    ? round(($totalPresent / ($totalPresent + $totalAbsent)) * 100, 1)
                    : 0,
            ];
        } catch (\Throwable $e) {
            // Keep defaults.
        }

        try {
            $pendingQuery = ESBTPInscription::query()->where(function ($q) {
                $q->whereIn('status', ['en_attente', 'pending'])->orWhere(function ($subQ) {
                    $subQ->where('status', 'active')
                        ->where(function ($wq) {
                            $wq->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation'])
                                ->orWhereNull('workflow_step');
                        });
                });
            });
            if ($anneeEnCours) {
                $pendingQuery->where('annee_universitaire_id', $anneeEnCours->id);
            }
            $data['pendingInscriptionsCount'] = $pendingQuery->count();
        } catch (\Throwable $e) {
            // Keep defaults.
        }

        try {
            $data['academicHealth'] = app(AcademicPilotageSummaryService::class)->summarize(
                $anneeEnCours?->id,
                'annuel',
                null,
                null
            );
        } catch (\Throwable $e) {
            // Keep defaults when the pilotage tables are not available.
        }


        try {
            if (Auth::user()?->can('finance.unpaid_count.view')) {
                $data['unpaidStudentsCount'] = app(UnpaidStudentCountService::class)->count($anneeEnCours?->id);
            }
        } catch (\Throwable $e) {
            // Keep the unpaid count at zero when finance tables are unavailable.
        }

        return $data;
    }

    public function jsonPayload(): array
    {
        $data = $this->build();
        unset($data['user'], $data['anneeEnCours']);
        $this->assertNoAmounts($data);

        return $data;
    }

    private function assertNoAmounts(array $payload): void
    {
        $encoded = strtolower(json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '');
        foreach (['montant', 'fcfa', 'amount', 'totalencaisse'] as $needle) {
            if (str_contains($encoded, $needle)) {
                throw new \RuntimeException('Directeur des etudes payloads must not expose financial amounts.');
            }
        }
    }
}
