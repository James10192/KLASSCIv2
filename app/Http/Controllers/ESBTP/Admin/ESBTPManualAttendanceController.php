<?php

namespace App\Http\Controllers\ESBTP\Admin;

use App\Http\Controllers\Controller;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPEnseignant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ESBTPManualAttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:superAdmin']);
    }

    public function index()
    {
        $teachers = ESBTPEnseignant::with('user')->get();
        $recentManualAttendances = ESBTPTeacherAttendance::with(['teacher.user', 'markedBy.user'])
            ->where('is_manual', true)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('esbtp.admin.attendance.manual-marking', compact('teachers', 'recentManualAttendances'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:esbtp_enseignants,id',
            'date' => 'required|date',
            'status' => 'required|in:present,late,absent',
            'reason' => 'required|string|max:255'
        ]);

        try {
            // Check for existing attendance
            $existingAttendance = ESBTPTeacherAttendance::where('teacher_id', $request->teacher_id)
                ->whereDate('date', $request->date)
                ->first();

            if ($existingAttendance) {
                // Update existing attendance
                $existingAttendance->update([
                    'status' => $request->status,
                    'is_manual' => true,
                    'manual_reason' => $request->reason,
                    'marked_by' => auth()->id(),
                    'updated_at' => now()
                ]);

                $attendance = $existingAttendance;
            } else {
                // Create new attendance record
                $attendance = ESBTPTeacherAttendance::create([
                    'teacher_id' => $request->teacher_id,
                    'date' => $request->date,
                    'status' => $request->status,
                    'is_manual' => true,
                    'manual_reason' => $request->reason,
                    'marked_by' => auth()->id()
                ]);
            }

            // Log the manual attendance action
            Log::info('Manual attendance marked by admin', [
                'attendance_id' => $attendance->id,
                'teacher_id' => $request->teacher_id,
                'status' => $request->status,
                'reason' => $request->reason,
                'admin_id' => auth()->id(),
                'is_update' => isset($existingAttendance)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Présence marquée manuellement avec succès',
                'attendance' => $attendance
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking manual attendance by admin', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage manuel: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'teacher_ids' => 'required|array',
            'teacher_ids.*' => 'exists:esbtp_enseignants,id',
            'date' => 'required|date',
            'status' => 'required|in:present,late,absent',
            'reason' => 'required|string|max:255'
        ]);

        try {
            $successCount = 0;
            $failureCount = 0;

            foreach ($request->teacher_ids as $teacherId) {
                try {
                    ESBTPTeacherAttendance::updateOrCreate(
                        [
                            'teacher_id' => $teacherId,
                            'date' => $request->date
                        ],
                        [
                            'status' => $request->status,
                            'is_manual' => true,
                            'manual_reason' => $request->reason,
                            'marked_by' => auth()->id()
                        ]
                    );

                    $successCount++;
                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Error in bulk attendance marking', [
                        'teacher_id' => $teacherId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log the bulk operation
            Log::info('Bulk manual attendance completed', [
                'total' => count($request->teacher_ids),
                'success' => $successCount,
                'failure' => $failureCount,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Opération terminée: $successCount succès, $failureCount échecs"
            ]);

        } catch (\Exception $e) {
            Log::error('Error in bulk attendance operation', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'opération en masse: ' . $e->getMessage()
            ], 500);
        }
    }
}
