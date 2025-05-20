<?php

namespace App\Http\Controllers\ESBTP\Admin;

use App\Http\Controllers\Controller;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPAttendanceCode;
use App\Models\ESBTPEnseignant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ESBTPForgottenCodeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:secretary,superAdmin']);
    }

    public function index()
    {
        $teachers = ESBTPEnseignant::with('user')->get();
        $recentCodes = ESBTPAttendanceCode::with('used_by.user')
            ->whereDate('date', Carbon::today())
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('esbtp.admin.attendance.forgotten-codes', compact('teachers', 'recentCodes'));
    }

    public function generateManualCode(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:esbtp_enseignants,id',
            'reason' => 'required|string|max:255'
        ]);

        try {
            // Generate a new code
            $code = $this->generateUniqueCode();

            // Create the attendance code record
            $attendanceCode = ESBTPAttendanceCode::create([
                'code' => $code,
                'date' => Carbon::today(),
                'expires_at' => Carbon::now()->addHours(24),
                'is_manual' => true,
                'manual_reason' => $request->reason,
                'created_by' => auth()->id()
            ]);

            // Log the manual code generation
            Log::info('Manual attendance code generated', [
                'code_id' => $attendanceCode->id,
                'teacher_id' => $request->teacher_id,
                'reason' => $request->reason,
                'generated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Code généré avec succès',
                'code' => $code
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating manual code', [
                'error' => $e->getMessage(),
                'teacher_id' => $request->teacher_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du code: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markManualAttendance(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:esbtp_enseignants,id',
            'date' => 'required|date',
            'status' => 'required|in:present,late,absent',
            'reason' => 'required|string|max:255'
        ]);

        try {
            $attendance = ESBTPTeacherAttendance::create([
                'teacher_id' => $request->teacher_id,
                'date' => $request->date,
                'status' => $request->status,
                'is_manual' => true,
                'manual_reason' => $request->reason,
                'marked_by' => auth()->id()
            ]);

            // Log the manual attendance marking
            Log::info('Manual attendance marked', [
                'attendance_id' => $attendance->id,
                'teacher_id' => $request->teacher_id,
                'status' => $request->status,
                'reason' => $request->reason,
                'marked_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Présence marquée manuellement avec succès',
                'attendance' => $attendance
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking manual attendance', [
                'error' => $e->getMessage(),
                'teacher_id' => $request->teacher_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage manuel: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateUniqueCode()
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 6));
        } while (ESBTPAttendanceCode::where('code', $code)->exists());

        return $code;
    }
}
