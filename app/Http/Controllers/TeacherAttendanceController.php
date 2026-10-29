<?php

namespace App\Http\Controllers;

use App\Models\TeacherAttendance;
use App\Models\Teacher;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeacherAttendanceController extends Controller
{
    /**
     * Display a listing of the teacher attendances.
     */
    public function index(Request $request)
    {
        $departments = Department::all();

        $departmentId = $request->department_id;
        $date = $request->date ? date('Y-m-d', strtotime($request->date)) : date('Y-m-d');

        $teachers = collect();
        $attendances = collect();

        if ($departmentId) {
            $query = Teacher::with('user');
            
            if ($departmentId != 'all') {
                $query->where('department_id', $departmentId);
            }
            
            $teachers = $query->get();

            $attendances = TeacherAttendance::where('date', $date)
                ->get()
                ->keyBy('teacher_id');
        }

        return view('teacher_attendances.index', compact('departments', 'teachers', 'attendances', 'departmentId', 'date'));
    }

    /**
     * Store or update multiple teacher attendances.
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'teacher_ids' => 'required|array',
            'teacher_ids.*' => 'exists:teachers,id',
            'statuses' => 'required|array',
            'statuses.*' => 'in:present,absent,late',
            'remarks' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $date = date('Y-m-d', strtotime($request->date));
            $teacherIds = $request->teacher_ids;
            $statuses = $request->statuses;
            $remarks = $request->remarks ?? [];

            foreach ($teacherIds as $index => $teacherId) {
                TeacherAttendance::updateOrCreate(
                    [
                        'teacher_id' => $teacherId,
                        'date' => $date,
                    ],
                    [
                        'status' => $statuses[$index],
                        'remark' => $remarks[$index] ?? null,
                        'taken_by' => Auth::id(),
                    ]
                );
            }

            DB::commit();

            return redirect()->route('teacher-attendances.index', ['date' => $date, 'department_id' => $request->department_id])
                ->with('success', 'Présences des enseignants enregistrées avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'enregistrement des présences: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the teacher attendance report.
     */
    public function report(Request $request)
    {
        $departments = Department::all();

        $departmentId = $request->department_id;
        $startDate = $request->start_date ? date('Y-m-d', strtotime($request->start_date)) : date('Y-m-01');
        $endDate = $request->end_date ? date('Y-m-d', strtotime($request->end_date)) : date('Y-m-t');

        $teachers = collect();
        $attendanceSummary = [];
        $dateRange = [];

        if ($startDate && $endDate) {
            $query = Teacher::with('user', 'department');
            
            if ($departmentId && $departmentId != 'all') {
                $query->where('department_id', $departmentId);
            }
            
            $teachers = $query->get();

            // Générer la plage de dates
            $currentDate = strtotime($startDate);
            $endDateTimestamp = strtotime($endDate);
            
            while ($currentDate <= $endDateTimestamp) {
                $dateRange[] = date('Y-m-d', $currentDate);
                $currentDate = strtotime('+1 day', $currentDate);
            }

            // Récupérer toutes les présences pour la période
            $attendances = TeacherAttendance::whereBetween('date', [$startDate, $endDate])
                ->get();

            // Organiser les présences par enseignant et par date
            foreach ($teachers as $teacher) {
                $teacherAttendances = $attendances->where('teacher_id', $teacher->id)->keyBy('date');
                
                $summary = [
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'total' => count($dateRange),
                    'percentage' => 0,
                    'dates' => [],
                ];
                
                foreach ($dateRange as $date) {
                    if (isset($teacherAttendances[$date])) {
                        $status = $teacherAttendances[$date]->status;
                        $summary[$status]++;
                        $summary['dates'][$date] = $status;
                    } else {
                        $summary['dates'][$date] = null;
                    }
                }
                
                // Calculer le pourcentage de présence
                if ($summary['total'] > 0) {
                    $summary['percentage'] = round(($summary['present'] + $summary['late']) / $summary['total'] * 100, 2);
                }
                
                $attendanceSummary[$teacher->id] = $summary;
            }
        }

        return view('teacher_attendances.report', compact('departments', 'teachers', 'attendanceSummary', 'dateRange', 'departmentId', 'startDate', 'endDate'));
    }

    /**
     * Display the teacher's attendance details.
     */
    public function teacherDetails(Teacher $teacher, Request $request)
    {
        $startDate = $request->start_date ? date('Y-m-d', strtotime($request->start_date)) : date('Y-m-01');
        $endDate = $request->end_date ? date('Y-m-d', strtotime($request->end_date)) : date('Y-m-t');
        
        $attendances = $teacher->attendances()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
        
        $summary = [
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'total' => $attendances->count(),
        ];
        
        $summary['percentage'] = $summary['total'] > 0 
            ? round(($summary['present'] + $summary['late']) / $summary['total'] * 100, 2) 
            : 0;
        
        return view('teacher_attendances.teacher_details', compact('teacher', 'attendances', 'summary', 'startDate', 'endDate'));
    }
}