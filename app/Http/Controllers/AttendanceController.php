<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Session;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the attendances.
     */
    public function index(Request $request)
    {
        $query = Attendance::with(['student.user', 'teacher.user']);

        // Filtres
        if ($request->filled('class_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        if ($request->filled('section_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderBy('date', 'desc')->paginate(15);
        $classes = ClassModel::all();
        $sections = Section::all();

        return view('attendances.index', compact('attendances', 'classes', 'sections'));
    }

    /**
     * Show the form for creating a new attendance.
     */
    public function create()
    {
        $classes = ClassModel::all();
        $sections = Section::all();
        $teachers = Teacher::with('user')->get();

        return view('attendances.create', compact('classes', 'sections', 'teachers'));
    }

    /**
     * Store a newly created attendance in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
            'teacher_id' => 'required|exists:teachers,id',
        ]);

        $classId = $request->class_id;
        $sectionId = $request->section_id;
        $date = $request->date;
        $teacherId = $request->teacher_id;

        // Récupérer tous les étudiants de cette classe et section
        $students = Student::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->get();

        // Vérifier si des présences existent déjà pour cette date, classe et section
        $existingAttendances = Attendance::whereDate('date', $date)
            ->whereHas('student', function($query) use ($classId, $sectionId) {
                $query->where('class_id', $classId)
                    ->where('section_id', $sectionId);
            })
            ->exists();

        if ($existingAttendances) {
            return redirect()->back()
                ->with('error', 'Des présences existent déjà pour cette date, classe et section.')
                ->withInput();
        }

        // Créer les présences pour tous les étudiants
        foreach ($students as $student) {
            Attendance::create([
                'student_id' => $student->id,
                'teacher_id' => $teacherId,
                'date' => $date,
                'status' => 'present', // Par défaut, tous les étudiants sont présents
                'remarks' => '',
                'created_by' => Auth::id(),
            ]);
        }

        return redirect()->route('attendances.index')
            ->with('success', 'Présences créées avec succès pour ' . $students->count() . ' étudiants.');
    }

    /**
     * Display the specified attendance.
     */
    public function show(Attendance $attendance)
    {
        $attendance->load(['student.user', 'teacher.user']);

        return view('attendances.show', compact('attendance'));
    }

    /**
     * Show the form for editing the specified attendance.
     */
    public function edit(Attendance $attendance)
    {
        $attendance->load(['student.user', 'teacher.user']);
        $teachers = Teacher::with('user')->get();

        return view('attendances.edit', compact('attendance', 'teachers'));
    }

    /**
     * Update the specified attendance in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        $request->validate([
            'status' => 'required|in:present,absent,late,excused',
            'remarks' => 'nullable|string|max:255',
            'teacher_id' => 'required|exists:teachers,id',
        ]);

        $attendance->update([
            'status' => $request->status,
            'remarks' => $request->remarks,
            'teacher_id' => $request->teacher_id,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('attendances.index')
            ->with('success', 'Présence mise à jour avec succès.');
    }

    /**
     * Remove the specified attendance from storage.
     */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return redirect()->route('attendances.index')
            ->with('success', 'Présence supprimée avec succès.');
    }

    /**
     * Display the attendance report.
     */
    public function report(Request $request)
    {
        $classes = ClassModel::all();
        $sections = Section::all();

        $students = collect();
        $attendanceData = [];
        $selectedClass = null;
        $selectedSection = null;
        $selectedMonth = null;
        $selectedYear = null;

        if ($request->filled(['class_id', 'section_id', 'month', 'year'])) {
            $selectedClass = ClassModel::findOrFail($request->class_id);
            $selectedSection = Section::findOrFail($request->section_id);
            $selectedMonth = $request->month;
            $selectedYear = $request->year;

            // Récupérer tous les étudiants de cette classe et section
            $students = Student::where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->with('user')
                ->get();

            // Récupérer toutes les présences pour ce mois et cette année
            $startDate = "{$selectedYear}-{$selectedMonth}-01";
            $endDate = date('Y-m-t', strtotime($startDate));

            $attendances = Attendance::whereHas('student', function($query) use ($request) {
                    $query->where('class_id', $request->class_id)
                        ->where('section_id', $request->section_id);
                })
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->groupBy('student_id')
                ->map(function($items) {
                    return $items->keyBy(function($item) {
                        return date('j', strtotime($item->date));
                    });
                });

            // Préparer les données pour l'affichage
            $daysInMonth = date('t', strtotime($startDate));

            foreach ($students as $student) {
                $attendanceData[$student->id] = [];

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $attendanceData[$student->id][$day] = $attendances->get($student->id, collect())->get($day);
                }
            }
        }

        return view('attendances.report', compact(
            'classes',
            'sections',
            'students',
            'attendanceData',
            'selectedClass',
            'selectedSection',
            'selectedMonth',
            'selectedYear'
        ));
    }

    /**
     * Mark a student's attendance (via AJAX).
     */
    public function mark(Request $request)
    {
        // Valider les données reçues
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,excused',
        ]);

        // Récupérer l'enseignant connecté
        $teacher = null;
        if (Auth::user()->can('can_teach')) {
            $teacher = Teacher::where('user_id', Auth::id())->first();
        } else {
            // Si ce n'est pas un enseignant, utiliser le premier enseignant disponible
            $teacher = Teacher::first();
        }

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun enseignant trouvé pour marquer la présence.'
            ], 400);
        }

        // Vérifier si une présence existe déjà pour cet étudiant à cette date
        $attendance = Attendance::where('student_id', $request->student_id)
            ->whereDate('date', $request->date)
            ->first();

        if ($attendance) {
            // Mettre à jour la présence existante
            $attendance->update([
                'status' => $request->status,
                'teacher_id' => $teacher->id,
                'updated_by' => Auth::id(),
            ]);
        } else {
            // Créer une nouvelle présence
            $attendance = Attendance::create([
                'student_id' => $request->student_id,
                'teacher_id' => $teacher->id,
                'date' => $request->date,
                'status' => $request->status,
                'remarks' => '',
                'created_by' => Auth::id(),
            ]);
        }

        // Retourner une réponse JSON
        return response()->json([
            'success' => true,
            'message' => 'Présence mise à jour avec succès',
            'attendance' => $attendance
        ]);
    }

    /**
     * Mark all students' attendance for a class (via AJAX).
     */
    public function markAll(Request $request)
    {
        // Valider les données reçues
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,excused',
        ]);

        // Récupérer l'enseignant connecté
        $teacher = null;
        if (Auth::user()->can('can_teach')) {
            $teacher = Teacher::where('user_id', Auth::id())->first();
        } else {
            // Si ce n'est pas un enseignant, utiliser le premier enseignant disponible
            $teacher = Teacher::first();
        }

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun enseignant trouvé pour marquer les présences.'
            ], 400);
        }

        // Récupérer tous les étudiants de cette classe et section
        $students = Student::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->get();

        $studentIds = [];

        // Mettre à jour ou créer les présences pour tous les étudiants
        foreach ($students as $student) {
            $attendance = Attendance::where('student_id', $student->id)
                ->whereDate('date', $request->date)
                ->first();

            if ($attendance) {
                // Mettre à jour la présence existante
                $attendance->update([
                    'status' => $request->status,
                    'teacher_id' => $teacher->id,
                    'updated_by' => Auth::id(),
                ]);
            } else {
                // Créer une nouvelle présence
                $attendance = Attendance::create([
                    'student_id' => $student->id,
                    'teacher_id' => $teacher->id,
                    'date' => $request->date,
                    'status' => $request->status,
                    'remarks' => '',
                    'created_by' => Auth::id(),
                ]);
            }

            $studentIds[] = $student->id;
        }

        // Retourner une réponse JSON
        return response()->json([
            'success' => true,
            'message' => 'Présences mises à jour avec succès',
            'students' => $studentIds
        ]);
    }

    /**
     * Display the student's attendance details.
     */
    public function studentDetails(Student $student, Request $request)
    {
        $startDate = $request->start_date ? date('Y-m-d', strtotime($request->start_date)) : date('Y-m-01');
        $endDate = $request->end_date ? date('Y-m-d', strtotime($request->end_date)) : date('Y-m-t');

        $attendances = $student->attendances()
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

        return view('attendances.student_details', compact('student', 'attendances', 'summary', 'startDate', 'endDate'));
    }

    /**
     * Affiche la page pour marquer les présences.
     */
    public function markPage(Request $request)
    {
        $classes = ClassModel::all();
        $sections = Section::all();

        $students = collect();
        $attendances = collect();
        $date = $request->date ? date('Y-m-d', strtotime($request->date)) : date('Y-m-d');

        if ($request->filled(['class_id', 'section_id'])) {
            // Récupérer tous les étudiants de cette classe et section
            $students = Student::where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->with('user')
                ->get();

            // Récupérer les présences existantes pour cette date
            $attendances = Attendance::whereDate('date', $date)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');
        }

        return view('attendances.mark', compact('classes', 'sections', 'students', 'attendances', 'date'));
    }
}
