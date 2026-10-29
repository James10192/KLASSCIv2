<?php

namespace App\Http\Controllers\ESBTP\Admin;

use App\Http\Controllers\Controller;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPAttendanceSettings;
use App\Models\ESBTPSeanceCours;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PDF;

class ESBTPTeacherAttendanceController extends Controller
{
    public function index()
    {
        // Check if this is a teacher accessing their attendance marking page
        // L'enseignant émarge sur « Mes cours du jour ». Cette page en portait une
        // seconde copie, avec ses propres délais écrits en dur : elle renvoie
        // désormais vers l'unique écran d'émargement.
        // Test d'identité sans passe-droit : `can()` est vrai pour superAdmin
        // (Gate::before), qui serait renvoyé vers un écran réservé aux enseignants.
        $user = auth()->user();
        if ($user->hasRole('enseignant') && ! $user->can('attendances.generate_codes')) {
            return redirect()->route('esbtp.teacher-attendance.index');
        }

        // Admin view
        $date = request('date', now()->toDateString());
        $dailyCode = ESBTPDailyCode::whereDate('created_at', today())
            ->where('status', 'active')
            ->latest()
            ->first();

        $todayAttendances = ESBTPTeacherAttendance::with(['teacher', 'course.matiere', 'course.emploiTemps.classe'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        $codeStats = $dailyCode ? $dailyCode->getAttemptsStatistics() : null;
        $settings = ESBTPAttendanceSettings::getAll();
        $prolongationsEnAttente = \App\Models\ESBTPProlongationSeance::where('statut', \App\Models\ESBTPProlongationSeance::EN_ATTENTE)->count();

        return view('esbtp.admin.attendance.index', compact('dailyCode', 'todayAttendances', 'codeStats', 'settings', 'prolongationsEnAttente', 'date'));
    }

    public function store(Request $request)
    {
        // Un enseignant qui émarge avec son code passe par le seul chemin
        // d'émargement, qui applique les délais réglés par l'école et la
        // prolongation éventuelle du cours.
        if ($request->has('code') && $request->has('course_id')) {
            return app(\App\Http\Controllers\ESBTP\TeacherAttendanceController::class)->sign($request);
        }

        // Admin attendance marking (existing functionality)
        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:esbtp_seance_cours,id',
            'status' => 'required|in:present,absent,late',
            'remarks' => 'nullable|string',
        ]);

        $attendance = ESBTPTeacherAttendance::create([
            'teacher_id' => $validated['teacher_id'],
            'course_id' => $validated['course_id'],
            'date' => now()->toDateString(),
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Présence enregistrée avec succès');
    }

    public function report(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'enseignant_id' => 'nullable|exists:users,id',
            'matiere_id' => 'nullable|exists:esbtp_matieres,id',
            'status' => 'nullable|in:present,late,absent',
            'validation_status' => 'nullable|in:pending,validated,rejected',
            'export_format' => 'nullable|in:csv,pdf',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();

        $query = ESBTPTeacherAttendance::with(['teacher', 'course.matiere'])
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        if ($request->enseignant_id) {
            $query->where('teacher_id', $request->enseignant_id);
        }

        if ($request->matiere_id) {
            $query->whereHas('course', function($q) use ($request) {
                $q->where('matiere_id', $request->matiere_id);
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->validation_status) {
            $query->where('validation_status', $request->validation_status);
        }

        $attendances = $query->orderBy('created_at', 'desc')->get();

        // **NOUVELLE LOGIQUE**: Compter seulement les émargements COMPLETS (début + fin)
        // Grouper par course_id + daily_code_id pour identifier les séances complètes
        $completedSessions = $attendances
            ->groupBy(function($att) {
                return $att->course_id . '_' . $att->daily_code_id;
            })
            ->filter(function($group) {
                // Une séance est complète si elle a à la fois type='start' ET type='end'
                $hasStart = $group->where('type', 'start')->isNotEmpty();
                $hasEnd = $group->where('type', 'end')->isNotEmpty();
                return $hasStart && $hasEnd;
            })
            ->count();

        // Compter les émargements de début seulement
        $startOnly = $attendances
            ->groupBy(function($att) {
                return $att->course_id . '_' . $att->daily_code_id;
            })
            ->filter(function($group) {
                $hasStart = $group->where('type', 'start')->isNotEmpty();
                $hasEnd = $group->where('type', 'end')->isNotEmpty();
                return $hasStart && !$hasEnd;
            })
            ->count();

        // Calculate detailed statistics
        $stats = [
            'total' => $attendances->count(), // Total d'enregistrements
            'total_sessions' => $attendances->groupBy(function($att) {
                return $att->course_id . '_' . $att->daily_code_id;
            })->count(), // Nombre de séances distinctes
            'completed_sessions' => $completedSessions, // Séances avec début ET fin
            'partial_sessions' => $startOnly, // Séances avec seulement début
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'validated' => $attendances->where('validation_status', 'validated')->count(),
            'rejected' => $attendances->where('validation_status', 'rejected')->count(),
            'pending' => $attendances->where('validation_status', 'pending')->count(),
            'attendance_rate' => $attendances->count() > 0
                ? round(($attendances->where('status', 'present')->count() + $attendances->where('status', 'late')->count()) / $attendances->count() * 100, 2)
                : 0,
            'validation_rate' => $attendances->count() > 0
                ? round($attendances->where('validation_status', 'validated')->count() / $attendances->count() * 100, 2)
                : 0,
            'daily_stats' => $attendances->groupBy(function($attendance) {
                return $attendance->created_at->format('Y-m-d');
            })->map(function($dayAttendances) {
                return [
                    'total' => $dayAttendances->count(),
                    'present' => $dayAttendances->where('status', 'present')->count(),
                    'late' => $dayAttendances->where('status', 'late')->count(),
                    'absent' => $dayAttendances->where('status', 'absent')->count(),
                ];
            }),
        ];

        // Handle exports
        if ($request->filled('export_format')) {
            return $this->exportReport($attendances, $stats, $request->export_format);
        }

        $enseignants = \App\Models\User::role('enseignant')->get();
        $matieres = ESBTPMatiere::all();

        return view('esbtp.admin.attendance.report', compact(
            'attendances',
            'enseignants',
            'matieres',
            'stats',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export attendance report in the specified format
     */
    private function exportReport($attendances, $stats, $format)
    {
        if ($format === 'csv') {
            return $this->exportToCsv($attendances);
        } else {
            return $this->exportToPdf($attendances, $stats);
        }
    }

    /**
     * Export attendance data to CSV
     */
    private function exportToCsv($attendances)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=attendance_report_' . now()->format('Y-m-d') . '.csv',
        ];

        $callback = function() use ($attendances) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, [
                'Date',
                'Enseignant',
                'Matière',
                'Status',
                'Heure d\'arrivée',
                'Code',
                'Validation',
                'Validé par',
                'Commentaires'
            ]);

            // Add data
            foreach ($attendances as $attendance) {
                fputcsv($file, [
                    $attendance->created_at->format('Y-m-d H:i:s'),
                    $attendance->teacher->name,
                    $attendance->course->matiere->name ?? 'N/A',
                    $attendance->status,
                    $attendance->marked_at,
                    $attendance->code,
                    $attendance->validation_status,
                    $attendance->validator ? $attendance->validator->name : 'N/A',
                    $attendance->comments
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export attendance data to PDF
     */
    private function exportToPdf($attendances, $stats)
    {
        $pdf = PDF::loadView('esbtp.admin.attendance.pdf_report', compact('attendances', 'stats'));

        return $pdf->download('attendance_report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function update(Request $request, ESBTPTeacherAttendance $attendance)
    {
        $validated = $request->validate([
            'status' => 'required|in:present,absent,late',
            'remarks' => 'nullable|string',
        ]);

        $attendance->update($validated);

        return redirect()->back()->with('success', 'Présence mise à jour avec succès');
    }

    public function generateCode()
    {
        try {
            $dailyCode = ESBTPDailyCode::createDailyCode();

            return response()->json([
                'success' => true,
                'id' => $dailyCode->id,
                'code' => $dailyCode->code,
                'valid_until' => $dailyCode->valid_until->format('Y-m-d H:i:s'),
                'remaining_minutes' => $dailyCode->getRemainingValidityInMinutes()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du code: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelCode(ESBTPDailyCode $code)
    {
        try {
            $code->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Code annulé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation du code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update teacher attendance status (mark as absent/present manually)
     * Pour coordinateurs qui veulent marquer un enseignant absent directement
     */
    public function updateStatus(Request $request, $seanceId)
    {
        \Log::info('🔵 START updateStatus', [
            'seance_id' => $seanceId,
            'status' => $request->status,
            'user' => auth()->id()
        ]);

        $request->validate([
            'status' => 'required|in:present,absent,late',
            'type' => 'nullable|in:start,end',
            'notes' => 'nullable|string'
        ]);

        try {
            $seanceCours = ESBTPSeanceCours::with(['teacher', 'matiere', 'emploiTemps.classe'])->findOrFail($seanceId);
            $type = $request->type ?? 'start'; // Par défaut début

            // Chercher un émargement existant (d'aujourd'hui OU de la date de séance)
            $attendance = ESBTPTeacherAttendance::where('course_id', $seanceId)
                ->where('type', $type)
                ->where(function($query) use ($seanceCours) {
                    $query->whereDate('date', today())
                          ->orWhereDate('date', \Carbon\Carbon::parse($seanceCours->date_seance));
                })
                ->first();

            if (!$attendance) {
                // 🆕 CRÉER un attendance d'aujourd'hui pour le marquage manuel
                // IMPORTANT: Ceci est un marquage ADMINISTRATIF, pas un émargement enseignant
                // La séance porte un esbtp_teachers.id ; l'émargement attend le users.id
                // du compte (FK). Sans compte rattaché, la ligne ne peut pas exister.
                $teacherUserId = $seanceCours->teacher?->user_id;
                if (!$teacherUserId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucun compte utilisateur rattaché à l\'enseignant de cette séance : impossible d\'enregistrer un émargement.',
                    ], 422);
                }

                \Log::info('🆕 Création attendance manuel (statut: non émargé → ' . $request->status . ')', [
                    'seance_id' => $seanceId,
                    'teacher_id' => $teacherUserId,
                    'type' => $type,
                    'date' => today(),
                    'status' => $request->status
                ]);

                $attendance = ESBTPTeacherAttendance::create([
                    'teacher_id' => $teacherUserId,
                    'course_id' => $seanceId,
                    'date' => today(), // Date du marquage manuel, PAS la date de séance originale
                    'status' => $request->status,
                    'type' => $type,
                    'attempts' => 0, // 0 = marquage manuel (pas d'émargement enseignant)
                    'validated_at' => now()
                ]);

                \Log::info('✅ Attendance manuel créé', [
                    'id' => $attendance->id,
                    'status' => $attendance->status
                ]);
            } else {
                // ✅ Mettre à jour l'attendance existant - SEULEMENT le statut
                \Log::info('📝 Avant update', [
                    'id' => $attendance->id,
                    'old_status' => $attendance->status,
                    'new_status' => $request->status
                ]);

                $attendance->status = $request->status;
                $attendance->save();

                \Log::info('✅ Attendance updated', [
                    'id' => $attendance->id,
                    'status_after_save' => $attendance->fresh()->status
                ]);
            }

            // ⚠️ Le workflow n'est JAMAIS modifié par le marquage manuel
            \Log::info('ℹ️ Workflow non modifié - marquage manuel ne change pas le workflow officiel');

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ ERROR updateStatus', [
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh single seance row (pour AJAX après update status)
     * Similaire au pattern de paiements.refresh-ligne
     */
    public function refreshSeanceLigne($seanceId)
    {
        \Log::info('🔄 Refresh seance ligne', ['seance_id' => $seanceId]);

        try {
            // IMPORTANT: Forcer NOUVEAU chargement complet sans aucun cache
            // Ne PAS utiliser with() car ça met en cache les relations
            $seance = ESBTPSeanceCours::findOrFail($seanceId);

            // Utiliser unsetRelation() puis load() pour forcer le rechargement depuis la DB
            $seance->unsetRelation('teacherAttendances');
            $seance->load([
                'teacher.user',
                'matiere',
                'emploiTemps.classe',
                'teacherAttendances' // Charger depuis DB après avoir unset le cache
            ]);

            \Log::info('📊 Attendances après reload', [
                'seance_id' => $seanceId,
                'attendances_count' => $seance->teacherAttendances->count(),
                'attendances' => $seance->teacherAttendances->map(function($att) {
                    return [
                        'id' => $att->id,
                        'status' => $att->status,
                        'type' => $att->type,
                        'date' => $att->date
                    ];
                })->toArray()
            ]);

            // Render la ligne HTML
            $html = view('esbtp.teacher-attendance.partials.seance-row', compact('seance'))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'seance_id' => $seanceId
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ ERROR refreshSeanceLigne', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'seance_ids' => 'required|array|min:1',
            'seance_ids.*' => 'exists:esbtp_seance_cours,id',
            'status' => 'required|in:present,absent',
        ]);

        $updatedCount = 0;
        foreach ($request->seance_ids as $seanceId) {
            $seance = ESBTPSeanceCours::find($seanceId);
            if (!$seance) continue;

            // Vérification serveur : séance passée ?
            $seanceEstFuture = false;
            if ($seance->date_seance) {
                $dateSeance = Carbon::parse($seance->date_seance)->startOfDay();
                $today = Carbon::today();
                if ($dateSeance->gt($today)) {
                    $seanceEstFuture = true;
                } elseif ($dateSeance->eq($today) && $seance->heure_fin) {
                    $seanceEstFuture = Carbon::parse($seance->heure_fin)->gt(now());
                }
            }
            if ($seanceEstFuture) continue;

            $teacher = $seance->teacher;
            if (!$teacher) continue;

            ESBTPTeacherAttendance::updateOrCreate(
                [
                    'course_id' => $seanceId,
                    'teacher_id' => $teacher->user_id,
                    'date' => $seance->date_seance ?? today(),
                ],
                [
                    'status' => $request->status,
                    'validated_at' => now(),
                    'type' => 'start',
                ]
            );
            $updatedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "$updatedCount séance(s) mise(s) à jour avec le statut " . $request->status,
            'updated_count' => $updatedCount,
        ]);
    }
}
