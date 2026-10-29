<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class GradeController extends Controller
{
    /**
     * Affiche la liste des notes.
     */
    public function index(Request $request)
    {
        $query = Grade::with(['student.user', 'subject', 'exam', 'semester', 'teacher.user']);
        
        // Filtres
        if ($request->filled('class_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }
        
        if ($request->filled('section_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        }
        
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }
        
        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }
        
        $grades = $query->orderBy('created_at', 'desc')->paginate(15);
        $classes = ClassModel::all();
        $sections = Section::all();
        $subjects = Subject::all();
        $exams = Exam::all();
        $semesters = Semester::all();
        
        return view('grades.index', compact('grades', 'classes', 'sections', 'subjects', 'exams', 'semesters'));
    }
    
    /**
     * Affiche le formulaire de création d'une note.
     */
    public function create()
    {
        $students = Student::with('user')->get();
        $subjects = Subject::all();
        $exams = Exam::all();
        $semesters = Semester::all();
        $classes = ClassModel::all();
        $sections = collect();
        
        // Si class_id est fourni dans la requête, charger les sections associées
        if(request('class_id')) {
            $sections = Section::where('class_id', request('class_id'))->get();
        }
        
        return view('grades.create', compact('students', 'subjects', 'exams', 'semesters', 'classes', 'sections'));
    }
    
    /**
     * Enregistre une nouvelle note.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'exam_id' => 'required|exists:exams,id',
            'semester_id' => 'required|exists:semesters,id',
            'marks_obtained' => 'required|numeric|min:0',
            'marks_total' => 'required|numeric|min:1',
            'remarks' => 'nullable|string',
        ]);
        
        // Vérifier si la note existe déjà
        $existingGrade = Grade::where('student_id', $request->student_id)
            ->where('subject_id', $request->subject_id)
            ->where('exam_id', $request->exam_id)
            ->where('semester_id', $request->semester_id)
            ->first();
            
        if ($existingGrade) {
            return redirect()->back()
                ->with('error', 'Une note existe déjà pour cet étudiant, cette matière, cet examen et ce semestre.')
                ->withInput();
        }
        
        // Calculer le pourcentage
        $percentage = ($request->marks_obtained / $request->marks_total) * 100;
        
        // Créer la note
        Grade::create([
            'student_id' => $request->student_id,
            'subject_id' => $request->subject_id,
            'exam_id' => $request->exam_id,
            'semester_id' => $request->semester_id,
            'marks_obtained' => $request->marks_obtained,
            'marks_total' => $request->marks_total,
            'percentage' => $percentage,
            'remarks' => $request->remarks,
            'teacher_id' => Auth::user()->teacher->id ?? null,
        ]);
        
        return redirect()->route('grades.index')
            ->with('success', 'Note enregistrée avec succès.');
    }
    
    /**
     * Affiche les détails d'une note.
     */
    public function show(Grade $grade)
    {
        $grade->load(['student.user', 'subject', 'exam', 'semester', 'teacher.user']);
        
        return view('grades.show', compact('grade'));
    }
    
    /**
     * Affiche le formulaire de modification d'une note.
     */
    public function edit(Grade $grade)
    {
        $students = Student::with('user')->get();
        $subjects = Subject::all();
        $exams = Exam::all();
        $semesters = Semester::all();
        
        return view('grades.edit', compact('grade', 'students', 'subjects', 'exams', 'semesters'));
    }
    
    /**
     * Met à jour une note.
     */
    public function update(Request $request, Grade $grade)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'exam_id' => 'required|exists:exams,id',
            'semester_id' => 'required|exists:semesters,id',
            'marks_obtained' => 'required|numeric|min:0',
            'marks_total' => 'required|numeric|min:1',
            'remarks' => 'nullable|string',
        ]);
        
        // Vérifier si la note existe déjà (en excluant la note actuelle)
        $existingGrade = Grade::where('student_id', $request->student_id)
            ->where('subject_id', $request->subject_id)
            ->where('exam_id', $request->exam_id)
            ->where('semester_id', $request->semester_id)
            ->where('id', '!=', $grade->id)
            ->first();
            
        if ($existingGrade) {
            return redirect()->back()
                ->with('error', 'Une note existe déjà pour cet étudiant, cette matière, cet examen et ce semestre.')
                ->withInput();
        }
        
        // Calculer le pourcentage
        $percentage = ($request->marks_obtained / $request->marks_total) * 100;
        
        // Mettre à jour la note
        $grade->update([
            'student_id' => $request->student_id,
            'subject_id' => $request->subject_id,
            'exam_id' => $request->exam_id,
            'semester_id' => $request->semester_id,
            'marks_obtained' => $request->marks_obtained,
            'marks_total' => $request->marks_total,
            'percentage' => $percentage,
            'remarks' => $request->remarks,
        ]);
        
        return redirect()->route('grades.index')
            ->with('success', 'Note mise à jour avec succès.');
    }
    
    /**
     * Supprime une note.
     */
    public function destroy(Grade $grade)
    {
        $grade->delete();
        
        return redirect()->route('grades.index')
            ->with('success', 'Note supprimée avec succès.');
    }
    
    /**
     * Affiche le rapport des notes d'un étudiant.
     */
    public function report($studentId, $semesterId = null)
    {
        $student = Student::with(['user', 'class', 'section'])->findOrFail($studentId);
        $semesters = Semester::all();
        
        if ($semesterId) {
            $semester = Semester::findOrFail($semesterId);
            $grades = Grade::where('student_id', $studentId)
                ->where('semester_id', $semesterId)
                ->with(['subject', 'exam'])
                ->get();
        } else {
            $semester = null;
            $grades = Grade::where('student_id', $studentId)
                ->with(['subject', 'exam', 'semester'])
                ->get();
        }
        
        // Organiser les notes par matière et par examen
        $organizedGrades = [];
        
        foreach ($grades as $grade) {
            $subjectId = $grade->subject_id;
            $examId = $grade->exam_id;
            
            if (!isset($organizedGrades[$subjectId])) {
                $organizedGrades[$subjectId] = [
                    'subject' => $grade->subject,
                    'exams' => [],
                    'total_percentage' => 0,
                    'count' => 0,
                ];
            }
            
            $organizedGrades[$subjectId]['exams'][$examId] = $grade;
            $organizedGrades[$subjectId]['total_percentage'] += $grade->percentage;
            $organizedGrades[$subjectId]['count']++;
        }
        
        // Calculer la moyenne par matière
        foreach ($organizedGrades as &$subjectGrade) {
            $subjectGrade['average'] = $subjectGrade['count'] > 0 
                ? $subjectGrade['total_percentage'] / $subjectGrade['count'] 
                : 0;
        }
        
        // Calculer la moyenne générale
        $totalPercentage = 0;
        $totalSubjects = count($organizedGrades);
        
        foreach ($organizedGrades as $subjectGrade) {
            $totalPercentage += $subjectGrade['average'];
        }
        
        $overallAverage = $totalSubjects > 0 ? $totalPercentage / $totalSubjects : 0;
        
        return view('grades.report', compact(
            'student', 
            'semester', 
            'semesters', 
            'organizedGrades', 
            'overallAverage'
        ));
    }
    
    /**
     * Calcule les moyennes pour un semestre.
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);
        
        $classId = $request->class_id;
        $sectionId = $request->section_id;
        $semesterId = $request->semester_id;
        
        // Récupérer les étudiants
        $query = Student::where('class_id', $classId);
        
        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }
        
        $students = $query->get();
        
        // Pour chaque étudiant, calculer les moyennes
        foreach ($students as $student) {
            $this->calculateStudentAverages($student->id, $semesterId);
        }
        
        return redirect()->back()
            ->with('success', 'Moyennes calculées avec succès.');
    }
    
    /**
     * Calcule les moyennes pour un étudiant.
     */
    private function calculateStudentAverages($studentId, $semesterId)
    {
        $grades = Grade::where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->with(['subject', 'exam'])
            ->get();
        
        // Organiser les notes par matière
        $subjectGrades = [];
        
        foreach ($grades as $grade) {
            $subjectId = $grade->subject_id;
            
            if (!isset($subjectGrades[$subjectId])) {
                $subjectGrades[$subjectId] = [
                    'subject' => $grade->subject,
                    'grades' => [],
                    'total_percentage' => 0,
                    'count' => 0,
                ];
            }
            
            $subjectGrades[$subjectId]['grades'][] = $grade;
            $subjectGrades[$subjectId]['total_percentage'] += $grade->percentage;
            $subjectGrades[$subjectId]['count']++;
        }
        
        // Calculer la moyenne par matière
        foreach ($subjectGrades as $subjectId => $data) {
            $average = $data['count'] > 0 ? $data['total_percentage'] / $data['count'] : 0;
            
            // Enregistrer ou mettre à jour la moyenne
            DB::table('subject_averages')->updateOrInsert(
                [
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'semester_id' => $semesterId,
                ],
                [
                    'average' => $average,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        // Calculer la moyenne générale
        $totalAverage = 0;
        $totalSubjects = count($subjectGrades);
        
        foreach ($subjectGrades as $data) {
            $average = $data['count'] > 0 ? $data['total_percentage'] / $data['count'] : 0;
            $totalAverage += $average;
        }
        
        $overallAverage = $totalSubjects > 0 ? $totalAverage / $totalSubjects : 0;
        
        // Enregistrer ou mettre à jour la moyenne générale
        DB::table('semester_averages')->updateOrInsert(
            [
                'student_id' => $studentId,
                'semester_id' => $semesterId,
            ],
            [
                'average' => $overallAverage,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        return $overallAverage;
    }
    
    /**
     * Génère le bulletin d'un étudiant.
     */
    public function bulletin($studentId, $semesterId)
    {
        $student = Student::with(['user', 'class', 'section'])->findOrFail($studentId);
        $semester = Semester::findOrFail($semesterId);
        
        // Récupérer les notes
        $grades = Grade::where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->with(['subject', 'exam'])
            ->get();
        
        // Organiser les notes par matière
        $subjectGrades = [];
        
        foreach ($grades as $grade) {
            $subjectId = $grade->subject_id;
            
            if (!isset($subjectGrades[$subjectId])) {
                $subjectGrades[$subjectId] = [
                    'subject' => $grade->subject,
                    'grades' => [],
                    'total_percentage' => 0,
                    'count' => 0,
                ];
            }
            
            $subjectGrades[$subjectId]['grades'][] = $grade;
            $subjectGrades[$subjectId]['total_percentage'] += $grade->percentage;
            $subjectGrades[$subjectId]['count']++;
        }
        
        // Calculer la moyenne par matière
        foreach ($subjectGrades as &$data) {
            $data['average'] = $data['count'] > 0 ? $data['total_percentage'] / $data['count'] : 0;
        }
        
        // Calculer la moyenne générale
        $totalAverage = 0;
        $totalSubjects = count($subjectGrades);
        
        foreach ($subjectGrades as $data) {
            $totalAverage += $data['average'];
        }
        
        $overallAverage = $totalSubjects > 0 ? $totalAverage / $totalSubjects : 0;
        
        // Récupérer les présences
        $attendanceStats = DB::table('attendances')
            ->where('student_id', $studentId)
            ->where('date', '>=', $semester->start_date)
            ->where('date', '<=', $semester->end_date)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
        
        $totalAttendance = array_sum($attendanceStats);
        $attendancePercentage = $totalAttendance > 0 
            ? round(($attendanceStats['present'] ?? 0) / $totalAttendance * 100) 
            : 0;
        
        // Générer le PDF
        $pdf = PDF::loadView('grades.bulletin_pdf', compact(
            'student', 
            'semester', 
            'subjectGrades', 
            'overallAverage',
            'attendancePercentage',
            'attendanceStats'
        ));
        
        return $pdf->download('bulletin_' . $student->user->name . '_' . $semester->name . '.pdf');
    }

    /**
     * Affiche le formulaire de sélection pour générer un bulletin.
     */
    public function selectBulletin()
    {
        $students = Student::with('user', 'class')->get();
        $semesters = Semester::all();
        
        return view('grades.select_bulletin', compact('students', 'semesters'));
    }
} 