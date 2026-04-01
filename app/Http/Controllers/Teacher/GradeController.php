<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Evaluation;
use App\Models\StudentGrade;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\User;
use App\Models\Matiere;
use App\Models\Classe;
use App\Models\SchoolYear;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class GradeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:teacher|enseignant');
        $this->middleware('permission:module.notes_evaluations.access');
    }

    /**
     * Display a listing of the evaluations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // Get the currently authenticated teacher
            $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
            
            // Get the current school year
            $currentSchoolYear = SchoolYear::where('is_current', true)->firstOrFail();
            
            // Build query with filters
            $query = Evaluation::where('teacher_id', $teacher->id)
                               ->where('school_year_id', $currentSchoolYear->id);

            // Apply filters if provided
            if ($request->filled('class_id')) {
                $query->where('class_id', $request->class_id);
            }
            
            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }
            
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            
            if ($request->filled('semester')) {
                $query->where('semester', $request->semester);
            }
            
            if ($request->filled('school_year_id')) {
                $query->where('school_year_id', $request->school_year_id);
            }
            
            // Get evaluations with pagination
            $evaluations = $query->with(['class', 'subject'])
                               ->orderBy('date', 'desc')
                               ->paginate(10);
            
            // Get classes and subjects taught by teacher
            $classes = Classe::whereHas('courses', function($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })->get();
            
            $subjects = Matiere::whereHas('courses', function($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })->get();
            
            // Get school years
            $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();
            
            // Return the view with data
            return view('teacher.grades.index', compact(
                'evaluations', 
                'classes', 
                'subjects', 
                'schoolYears', 
                'teacher', 
                'currentSchoolYear'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error in GradeController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors du chargement des évaluations.');
        }
    }

    /**
     * Show the form for creating a new evaluation.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            // Get the currently authenticated teacher
            $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
            
            // Get current school year
            $currentSchoolYear = SchoolYear::where('is_current', true)->firstOrFail();
            
            // Get classes and subjects taught by teacher
            $classes = Classe::whereHas('courses', function($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })->get();
            
            $subjects = collect(); // Empty collection initially
            
            // Pre-select a class if provided in the query string
            $selectedClassId = request('classe_id');
            
            if ($selectedClassId) {
                // Get subjects taught by this teacher in this class
                $subjects = Matiere::whereHas('courses', function($q) use ($teacher, $selectedClassId) {
                    $q->where('teacher_id', $teacher->id)
                      ->where('class_id', $selectedClassId);
                })->get();
            }
            
            return view('teacher.grades.create', compact(
                'teacher', 
                'classes', 
                'subjects', 
                'currentSchoolYear',
                'selectedClassId'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error in GradeController@create: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors du chargement du formulaire de création.');
        }
    }

    /**
     * Store a newly created evaluation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'class_id' => 'required|exists:classes,id',
                'subject_id' => 'required|exists:matieres,id',
                'type' => ['required', Rule::in(['examen', 'devoir', 'controle', 'tp', 'projet', 'autre'])],
                'date' => 'required|date',
                'semester' => 'required|integer|in:1,2',
                'total_points' => 'required|numeric|min:0|max:100',
                'coefficient' => 'nullable|integer|min:1|max:10',
                'passing_grade' => 'nullable|numeric|min:0|max:100',
                'description' => 'nullable|string|max:500',
            ]);
            
            // Get the currently authenticated teacher
            $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
            
            // Get current school year
            $currentSchoolYear = SchoolYear::where('is_current', true)->firstOrFail();
            
            // Begin transaction
            DB::beginTransaction();
            
            // Create evaluation
            $evaluation = new Evaluation();
            $evaluation->title = $validated['title'];
            $evaluation->class_id = $validated['class_id'];
            $evaluation->subject_id = $validated['subject_id'];
            $evaluation->teacher_id = $teacher->id;
            $evaluation->school_year_id = $currentSchoolYear->id;
            $evaluation->type = $validated['type'];
            $evaluation->date = $validated['date'];
            $evaluation->semester = $validated['semester'];
            $evaluation->total_points = $validated['total_points'];
            $evaluation->coefficient = $validated['coefficient'] ?? 1;
            $evaluation->passing_grade = $validated['passing_grade'] ?? null;
            $evaluation->description = $validated['description'] ?? null;
            $evaluation->is_published = false;
            $evaluation->created_by = Auth::id();
            
            $evaluation->save();
            
            // Commit transaction
            DB::commit();
            
            return redirect()->route('grades.edit', $evaluation->id)
                           ->with('success', 'Évaluation créée avec succès. Vous pouvez maintenant saisir les notes.');
            
        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();
            
            Log::error('Error in GradeController@store: ' . $e->getMessage());
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Une erreur est survenue lors de la création de l\'évaluation: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified evaluation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            // Get the teacher
            $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
            
            // Get the evaluation with relationships
            $evaluation = Evaluation::with(['class', 'subject', 'teacher', 'schoolYear', 'grades.student.user'])
                                  ->where('id', $id)
                                  ->where('teacher_id', $teacher->id)
                                  ->firstOrFail();
            
            // Get statistics
            $stats = [
                'total_students' => $evaluation->class->students()->count(),
                'grades_entered' => $evaluation->grades()->whereNotNull('grade')->count(),
                'absent_count' => $evaluation->absent_count,
                'exempt_count' => $evaluation->exempt_count,
                'present_count' => $evaluation->present_count,
                'min_grade' => $evaluation->minimum_grade,
                'max_grade' => $evaluation->maximum_grade,
                'avg_grade' => $evaluation->average_grade,
                'completion_percentage' => $evaluation->getCompletionPercentage(),
            ];
            
            // Get all student grades
            $studentGrades = $evaluation->grades()->with('student.user')->get()->groupBy('student_id');
            
            return view('teacher.grades.show', compact('evaluation', 'stats', 'studentGrades'));
            
        } catch (\Exception $e) {
            Log::error('Error in GradeController@show: ' . $e->getMessage());
            return redirect()->route('grades.index')
                           ->with('error', 'L\'évaluation demandée n\'existe pas ou vous n\'êtes pas autorisé à y accéder.');
        }
    }

    /**
     * Show the form for editing student grades.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            // Get the teacher
            $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
            
            // Get the evaluation
            $evaluation = Evaluation::with(['class', 'subject'])
                                  ->where('id', $id)
                                  ->where('teacher_id', $teacher->id)
                                  ->firstOrFail();
            
            // Get all students in the class
            $students = $evaluation->class->students()->with('user')->get();
            
            // Get existing grades
            $grades = StudentGrade::where('evaluation_id', $evaluation->id)->get();
            
            // Prepare data for the view
            $studentGrades = [];
            $studentComments = [];
            $studentAbsences = [];
            
            foreach ($grades as $grade) {
                $studentGrades[$grade->student_id] = $grade->grade;
                $studentComments[$grade->student_id] = $grade->comment;
                $studentAbsences[$grade->student_id] = ($grade->status === 'absent');
            }
            
            // Calculate statistics
            $gradesCount = $grades->whereNotNull('grade')->count();
            $averageGrade = $evaluation->average_grade;
            $minGrade = $evaluation->minimum_grade;
            $maxGrade = $evaluation->maximum_grade;
            
            return view('teacher.grades.edit', compact(
                'evaluation', 
                'students', 
                'studentGrades', 
                'studentComments', 
                'studentAbsences',
                'gradesCount',
                'averageGrade', 
                'minGrade', 
                'maxGrade'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error in GradeController@edit: ' . $e->getMessage());
            return redirect()->route('grades.index')
                           ->with('error', 'L\'évaluation demandée n\'existe pas ou vous n\'êtes pas autorisé à y accéder.');
        }
    }

    /**
     * Update student grades for the specified evaluation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            // Get the teacher
            $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
            
            // Get the evaluation
            $evaluation = Evaluation::where('id', $id)
                                  ->where('teacher_id', $teacher->id)
                                  ->firstOrFail();
            
            // Validate grades
            $validator = Validator::make($request->all(), [
                'grades.*' => 'nullable|numeric|min:0|max:' . $evaluation->total_points,
                'comments.*' => 'nullable|string|max:255',
                'absences.*' => 'nullable|boolean',
            ]);
            
            if ($validator->fails()) {
                return redirect()->back()
                               ->withErrors($validator)
                               ->withInput()
                               ->with('error', 'Erreur de validation. Veuillez vérifier les notes saisies.');
            }
            
            // Begin transaction
            DB::beginTransaction();
            
            // Get all students in the class
            $students = $evaluation->class->students()->pluck('id')->toArray();
            
            // Process each student's grade
            foreach ($students as $studentId) {
                $status = 'present';
                $grade = null;
                $comment = null;
                
                // Check if student is marked as absent
                if (isset($request->absences[$studentId]) && $request->absences[$studentId]) {
                    $status = 'absent';
                } else {
                    // Process grade only if student is not absent
                    if (isset($request->grades[$studentId]) && $request->grades[$studentId] !== '') {
                        $grade = $request->grades[$studentId];
                    }
                    
                    if (isset($request->comments[$studentId])) {
                        $comment = $request->comments[$studentId];
                    }
                }
                
                // Update or create the grade
                StudentGrade::updateOrCreate(
                    [
                        'evaluation_id' => $evaluation->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'grade' => $grade,
                        'status' => $status,
                        'comment' => $comment,
                        'updated_by' => Auth::id(),
                    ]
                );
            }
            
            // Commit transaction
            DB::commit();
            
            return redirect()->route('grades.index')
                           ->with('success', 'Les notes ont été enregistrées avec succès.');
            
        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();
            
            Log::error('Error in GradeController@update: ' . $e->getMessage());
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Une erreur est survenue lors de l\'enregistrement des notes: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified evaluation from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            // Get the teacher
            $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
            
            // Get the evaluation
            $evaluation = Evaluation::where('id', $id)
                                  ->where('teacher_id', $teacher->id)
                                  ->firstOrFail();
            
            // Begin transaction
            DB::beginTransaction();
            
            // Delete related student grades first
            StudentGrade::where('evaluation_id', $evaluation->id)->delete();
            
            // Delete the evaluation
            $evaluation->delete();
            
            // Commit transaction
            DB::commit();
            
            return redirect()->route('grades.index')
                           ->with('success', 'L\'évaluation a été supprimée avec succès.');
            
        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();
            
            Log::error('Error in GradeController@destroy: ' . $e->getMessage());
            return redirect()->back()
                           ->with('error', 'Une erreur est survenue lors de la suppression de l\'évaluation: ' . $e->getMessage());
        }
    }

    /**
     * Get subjects taught by the teacher in a specific class.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getSubjectsByClass(Request $request)
    {
        try {
            // Validate the class ID
            $validator = Validator::make($request->all(), [
                'class_id' => 'required|exists:classes,id',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['error' => 'ID de classe invalide.'], 400);
            }
            
            // Get the teacher
            $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
            
            // Get subjects taught by this teacher in this class
            $subjects = Matiere::whereHas('courses', function($q) use ($teacher, $request) {
                $q->where('teacher_id', $teacher->id)
                  ->where('class_id', $request->class_id);
            })->get();
            
            return response()->json([
                'subjects' => $subjects->map(function($subject) {
                    return [
                        'id' => $subject->id,
                        'nom' => $subject->nom,
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in GradeController@getSubjectsByClass: ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur est survenue lors de la récupération des matières.'], 500);
        }
    }
} 