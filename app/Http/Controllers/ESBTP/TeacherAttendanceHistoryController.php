<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ESBTPTeacherAttendance;
use Carbon\Carbon;

class TeacherAttendanceHistoryController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user();
        $query = ESBTPTeacherAttendance::where('teacher_id', $teacher->id);

        // Apply date range filter if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('marked_at', [$startDate, $endDate]);
        }

        // Apply status filter if provided
        if ($request->has('status')) {
            $query->where('validation_status', $request->status);
        }

        // Get paginated results
        $attendances = $query->orderBy('marked_at', 'desc')
            ->paginate(10);

        return view('esbtp.teacher.attendance.history', [
            'attendances' => $attendances,
            'startDate' => $request->start_date ?? null,
            'endDate' => $request->end_date ?? null,
            'status' => $request->status ?? null
        ]);
    }
}
