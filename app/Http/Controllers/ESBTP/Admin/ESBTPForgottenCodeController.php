<?php

namespace App\Http\Controllers\ESBTP\Admin;

use App\Http\Controllers\Controller;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPTeacher;
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
        $teachers = ESBTPTeacher::with('user')->get();
        $recentCodes = ESBTPDailyCode::with('generator')
            ->whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('esbtp.admin.attendance.forgotten-codes', compact('teachers', 'recentCodes'));
    }

    public function generateManualCode(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:255'
        ]);

        try {
            // Generate a new code
            $code = $this->generateUniqueCode();

            // Create the attendance code record
            $attendanceCode = ESBTPDailyCode::create([
                'code' => $code,
                'valid_from' => now(),
                'valid_until' => Carbon::now()->addHours(24),
                'is_active' => true,
                'status' => 'active',
                'description' => 'Code manuel: ' . $request->reason,
                'created_by' => auth()->id(),
                'type' => 'manuel'
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


    private function generateUniqueCode()
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 6));
        } while (ESBTPDailyCode::where('code', $code)->exists());

        return $code;
    }
}
