<?php

namespace App\Http\Controllers\ESBTP\Admin;

use App\Http\Controllers\Controller;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPEnseignant;
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
        $user = auth()->user();
        if ($user->hasRole(['enseignant', 'teacher'])) {
            return $this->showTeacherAttendancePage();
        }

        // Admin view
        $date = request('date', now()->toDateString());
        $dailyCode = ESBTPDailyCode::whereDate('created_at', today())
            ->where('status', 'active')
            ->latest()
            ->first();

        $todayAttendances = ESBTPTeacherAttendance::with(['teacher', 'course.matiere'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        $codeStats = $dailyCode ? $dailyCode->getAttemptsStatistics() : null;
        $settings = ESBTPAttendanceSettings::getAll();

        return view('esbtp.admin.attendance.index', compact('dailyCode', 'todayAttendances', 'codeStats', 'settings'));
    }

    /**
     * Show teacher attendance marking page with their courses
     */
    private function showTeacherAttendancePage()
    {
        $user = auth()->user();

        // Get today's courses for the teacher  
        $today = now()->format('Y-m-d');
        $dayOfWeek = now()->dayOfWeek; // 0=Sunday, 1=Monday, etc.
        // Convert to database format (1=Monday, 7=Sunday)
        $dayOfWeekDb = $dayOfWeek == 0 ? 7 : $dayOfWeek;
        
        $todayCourses = ESBTPSeanceCours::with(['matiere', 'emploiTemps.classe'])
            ->where('teacher_id', $user->id) // Direct teacher assignment on seance
            ->where('is_active', true)
            ->where('jour', $dayOfWeekDb)
            ->get();

        // Load teacher attendance status for each course
        $todayCourses->each(function($course) use ($user, $today) {
            $course->teacherAttendance = ESBTPTeacherAttendance::where('teacher_id', $user->id)
                ->where('course_id', $course->id)
                ->whereDate('date', $today)
                ->first();
        });

        return view('esbtp.attendance.mark', compact('todayCourses'));
    }

    public function store(Request $request)
    {
        // Check if this is teacher self-attendance marking
        if ($request->has('code') && $request->has('course_id')) {
            return $this->markTeacherAttendance($request);
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

    /**
     * Handle teacher self-attendance marking with code verification
     */
    private function markTeacherAttendance(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
            'course_id' => 'required|exists:esbtp_seance_cours,id'
        ]);

        // Find the active daily code
        $dailyCode = ESBTPDailyCode::where('code', $validated['code'])
            ->where('status', 'active')
            ->where('is_active', true)
            ->first();

        if (!$dailyCode) {
            return redirect()->back()->with('error', 'Code d\'émargement invalide ou expiré.');
        }

        if (!$dailyCode->isValid()) {
            return redirect()->back()->with('error', 'Code d\'émargement expiré.');
        }

        // Get the course (seance)
        $seanceCours = ESBTPSeanceCours::findOrFail($validated['course_id']);
        
        // Get current teacher
        $user = auth()->user();
        
        // Check if teacher is assigned to this course
        if ($seanceCours->teacher_id !== $user->id) {
            return redirect()->back()->with('error', 'Vous n\'êtes pas assigné à ce cours.');
        }
        
        // Check if teacher has already marked attendance for this course today
        $existingAttendance = ESBTPTeacherAttendance::where('teacher_id', $user->id)
            ->where('course_id', $seanceCours->id)
            ->whereDate('date', today())
            ->first();

        if ($existingAttendance) {
            return redirect()->back()->with('warning', 'Vous avez déjà émargé pour ce cours aujourd\'hui.');
        }

        // Create attendance record
        try {
            $attendance = ESBTPTeacherAttendance::create([
                'teacher_id' => $user->id,
                'course_id' => $seanceCours->id,
                'daily_code_id' => $dailyCode->id,
                'date' => now()->toDateString(),
                'status' => 'present', // Present/Done
                'attempts' => 1,
                'ip_address' => $request->ip(),
                'device_info' => json_encode(['user_agent' => $request->userAgent()]),
                'validated_at' => now()
            ]);

            // Record successful attempt on the daily code
            $dailyCode->recordAttempt(true);

            return redirect()->back()->with('success', 'Émargement enregistré avec succès.');

        } catch (\Exception $e) {
            // Record failed attempt
            $dailyCode->recordAttempt(false);
            
            return redirect()->back()->with('error', 'Erreur lors de l\'enregistrement de l\'émargement: ' . $e->getMessage());
        }
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

        // Calculate detailed statistics
        $stats = [
            'total' => $attendances->count(),
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
}
