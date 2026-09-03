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
        $user = auth()->user();

        // Paramètres de filtrage (bornés pour éviter toute valeur aberrante)
        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);
        $month = max(1, min(12, $month));
        $year = max(2000, min((int) Carbon::now()->year + 1, $year));

        // esbtp_teacher_attendances.teacher_id référence users.id.
        // Le filtre porte sur `date` (toujours renseignée) et non sur `validated_at`
        // qui reste nulle pour les séances marquées non émargées automatiquement.
        $baseQuery = ESBTPTeacherAttendance::query()
            ->where('teacher_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        $attendances = (clone $baseQuery)
            ->with(['course.matiere', 'course.classe', 'dailyCode'])
            ->orderBy('date', 'desc')
            ->orderBy('validated_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Les compteurs portent sur l'ensemble de la période, pas sur la page courante.
        $countsByStatus = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'total' => (int) $countsByStatus->sum(),
            'present' => (int) ($countsByStatus['present'] ?? 0),
            'late' => (int) ($countsByStatus['late'] ?? 0),
            'not_signed' => (int) ($countsByStatus['not_signed'] ?? 0) + (int) ($countsByStatus['absent'] ?? 0),
        ];

        return view('esbtp.teacher.attendance.history', compact('attendances', 'stats', 'month', 'year'));
    }
}
