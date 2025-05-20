<?php

namespace App\Http\Controllers\ESBTP\Admin;

use App\Http\Controllers\Controller;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPAttendanceSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\ESBTPEnseignant;
use App\Models\ESBTPClasse;
use Illuminate\Support\Str;
use App\Exports\TeacherAttendanceExport;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function index()
    {
        $dailyCode = ESBTPDailyCode::whereDate('created_at', today())
            ->where('status', 'active')
            ->latest()
            ->first();

        $todayAttendances = ESBTPTeacherAttendance::with(['enseignant', 'emploiDuTemps', 'validator'])
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();

        $codeStats = $dailyCode ? $dailyCode->getAttemptsStatistics() : null;
        $settings = ESBTPAttendanceSettings::getAll();

        return view('esbtp.admin.attendance.index', compact('dailyCode', 'todayAttendances', 'codeStats', 'settings'));
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

    public function validateAttendance(Request $request, ESBTPTeacherAttendance $attendance)
    {
        $request->validate([
            'validation_status' => 'required|in:validated,rejected',
            'validation_notes' => 'required_if:validation_status,rejected|nullable|string'
        ]);

        try {
            if ($request->validation_status === 'validated') {
                $attendance->validate(auth()->user(), $request->validation_notes);
            } else {
                $attendance->reject(auth()->user(), $request->validation_notes);
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut de validation mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation: ' . $e->getMessage()
            ], 500);
        }
    }

    public function settings()
    {
        $settings = [
            'geolocation' => ESBTPAttendanceSettings::getGeolocationSettings(),
            'time' => ESBTPAttendanceSettings::getTimeSettings(),
            'security' => ESBTPAttendanceSettings::getSecuritySettings()
        ];

        return view('esbtp.admin.attendance.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'geolocation_required' => 'required|boolean',
            'max_distance_meters' => 'required|integer|min:10|max:1000',
            'school_latitude' => 'required|numeric',
            'school_longitude' => 'required|numeric',
            'code_validity_hours' => 'required|integer|min:1|max:48',
            'allowed_early_minutes' => 'required|integer|min:0|max:60',
            'allowed_late_minutes' => 'required|integer|min:0|max:60',
            'display_code_duration' => 'required|integer|min:1|max:1440',
            'max_attempts' => 'required|integer|min:1|max:10',
            'block_duration_minutes' => 'required|integer|min:1|max:1440'
        ]);

        try {
            foreach ($request->except('_token') as $key => $value) {
                ESBTPAttendanceSettings::set($key, $value);
            }

            return redirect()->back()->with('success', 'Paramètres mis à jour avec succès');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour des paramètres: ' . $e->getMessage());
        }
    }

    public function report(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'enseignant_id' => 'nullable|exists:esbtp_enseignants,id',
            'export' => 'nullable|boolean'
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();

        $query = ESBTPTeacherAttendance::with(['enseignant', 'emploiDuTemps', 'validator'])
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        if ($request->enseignant_id) {
            $query->where('enseignant_id', $request->enseignant_id);
        }

        $attendances = $query->orderBy('created_at', 'desc')->get();
        $enseignants = ESBTPEnseignant::all();

        if ($request->export) {
            return Excel::download(
                new TeacherAttendanceExport($attendances),
                'rapport_emargement_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.xlsx'
            );
        }

        $stats = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'validated' => $attendances->where('validation_status', 'validated')->count(),
            'rejected' => $attendances->where('validation_status', 'rejected')->count(),
            'pending' => $attendances->where('validation_status', 'pending')->count()
        ];

        return view('esbtp.admin.attendance.report', compact('attendances', 'enseignants', 'stats', 'startDate', 'endDate'));
    }
}
