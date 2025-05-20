<?php

namespace App\Http\Controllers\ESBTP\Api;

use App\Http\Controllers\Controller;
use App\Models\ESBTPTeacherAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceSyncController extends Controller
{
    public function sync(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|exists:students,id',
                'date' => 'required|date',
                'status' => 'required|in:present,absent,late',
                'timestamp' => 'required|date'
            ]);

            // Convert timestamp to Carbon instance
            $timestamp = Carbon::parse($request->timestamp);

            // Check if there's a more recent attendance record
            $existingAttendance = ESBTPTeacherAttendance::where('student_id', $request->student_id)
                ->whereDate('date', $request->date)
                ->where('created_at', '>', $timestamp)
                ->first();

            if ($existingAttendance) {
                // A more recent record exists, return conflict
                return response()->json([
                    'success' => false,
                    'message' => 'Une version plus récente existe déjà',
                    'data' => $existingAttendance
                ], 409);
            }

            // Create or update attendance record
            $attendance = ESBTPTeacherAttendance::updateOrCreate(
                [
                    'student_id' => $request->student_id,
                    'date' => $request->date
                ],
                [
                    'status' => $request->status,
                    'sync_source' => 'offline_storage',
                    'created_at' => $timestamp
                ]
            );

            // Log the sync event
            Log::info('Attendance synced from offline storage', [
                'attendance_id' => $attendance->id,
                'student_id' => $request->student_id,
                'date' => $request->date,
                'status' => $request->status,
                'timestamp' => $timestamp
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Présence synchronisée avec succès',
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            Log::error('Error syncing attendance', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la synchronisation: ' . $e->getMessage()
            ], 500);
        }
    }
}
