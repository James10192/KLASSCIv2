<?php

namespace App\Http\Controllers\ESBTP\Admin;

use App\Http\Controllers\Controller;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPEnseignant;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPAttendanceSettings;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PDF;

class ESBTPTeacherAttendanceController extends Controller
{
    public function index()
    {
        $date = request('date', now()->toDateString());
        $dailyCode = ESBTPDailyCode::whereDate('created_at', today())
            ->where('status', 'active')
            ->latest()
            ->first();

        $todayAttendances = ESBTPTeacherAttendance::with(['enseignant', 'matiere'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        $codeStats = $dailyCode ? $dailyCode->getAttemptsStatistics() : null;
        $settings = ESBTPAttendanceSettings::getAll();

        return view('esbtp.admin.attendance.index', compact('dailyCode', 'todayAttendances', 'codeStats', 'settings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'enseignant_id' => 'required|exists:esbtp_enseignants,id',
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'status' => 'required|in:present,absent,late',
            'remarks' => 'nullable|string',
        ]);

        $attendance = ESBTPTeacherAttendance::create([
            'enseignant_id' => $validated['enseignant_id'],
            'matiere_id' => $validated['matiere_id'],
            'date' => now()->toDateString(),
            'time_in' => now()->toTimeString(),
            'status' => $validated['status'],
            'remarks' => $validated['remarks'],
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent()
        ]);

        return redirect()->back()->with('success', 'Présence enregistrée avec succès');
    }

    public function report(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'enseignant_id' => 'nullable|exists:esbtp_enseignants,id',
            'matiere_id' => 'nullable|exists:esbtp_matieres,id',
            'status' => 'nullable|in:present,late,absent',
            'validation_status' => 'nullable|in:pending,validated,rejected',
            'export_format' => 'nullable|in:csv,pdf',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();

        $query = ESBTPTeacherAttendance::with(['enseignant', 'matiere', 'validator'])
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        if ($request->enseignant_id) {
            $query->where('enseignant_id', $request->enseignant_id);
        }

        if ($request->matiere_id) {
            $query->where('matiere_id', $request->matiere_id);
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

        $enseignants = ESBTPEnseignant::all();
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
                    $attendance->enseignant->nom_complet,
                    $attendance->matiere->nom,
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
