<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Session;
use App\Models\ESBTPParent;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    /**
     * Display a listing of the students.
     */
    public function index(Request $request)
    {
        $query = Student::with(['user', 'class', 'section', 'session']);

        // Filtres
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('admission_no', 'like', "%{$search}%")
              ->orWhere('roll_no', 'like', "%{$search}%");
        }

        $students = $query->paginate(15);
        $classes = ClassModel::all();
        $sections = Section::all();
        $sessions = Session::all();

        return view('students.index', compact('students', 'classes', 'sections', 'sessions'));
    }

    /**
     * Show the form for creating a new student.
     */
    public function create()
    {
        $classes = ClassModel::all();
        $sections = Section::all();
        $sessions = Session::where('is_current', true)->first() ?? Session::latest()->first();
        $guardians = ESBTPParent::all();

        return view('students.create', compact('classes', 'sections', 'sessions', 'guardians'));
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'admission_no' => 'required|string|max:255|unique:students',
            'roll_no' => 'nullable|string|max:255',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:sessions,id',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'admission_date' => 'required|date',
            'profile_image' => 'nullable|image|max:2048',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'guardian_id' => 'nullable|exists:esbtp_parents,id',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();

        try {
            // Créer l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'student',
                'is_active' => true,
                'phone' => $request->phone,
            ]);

            // Gérer l'image de profil
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $user->profile_image = $path;
                $user->save();
            }

            // Créer l'étudiant
            $student = Student::create([
                'user_id' => $user->id,
                'admission_no' => $request->admission_no,
                'roll_no' => $request->roll_no,
                'class_id' => $request->class_id,
                'section_id' => $request->section_id,
                'session_id' => $request->session_id,
                'father_name' => $request->father_name,
                'mother_name' => $request->mother_name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'pincode' => $request->pincode,
                'admission_date' => $request->admission_date,
                'guardian_id' => $request->guardian_id,
            ]);

            DB::commit();

            return redirect()->route('students.index')
                ->with('success', 'Étudiant créé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création de l\'étudiant: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student)
    {
        $student->load(['user', 'class', 'section', 'session', 'guardian', 'attendances', 'grades.subject', 'certificates']);
        
        // Calculer le taux de présence
        $startDate = now()->subDays(30);
        $endDate = now();
        $attendancePercentage = $student->calculateAttendancePercentage($startDate, $endDate);
        
        return view('students.show', compact('student', 'attendancePercentage'));
    }

    /**
     * Show the form for editing the specified student.
     */
    public function edit(Student $student)
    {
        $student->load('user');
        $classes = ClassModel::all();
        $sections = Section::all();
        $sessions = Session::all();
        $guardians = ESBTPParent::all();

        return view('students.edit', compact('student', 'classes', 'sections', 'sessions', 'guardians'));
    }

    /**
     * Update the specified student in storage.
     */
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($student->user_id)],
            'admission_no' => ['required', 'string', 'max:255', Rule::unique('students')->ignore($student->id)],
            'roll_no' => 'nullable|string|max:255',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:sessions,id',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'admission_date' => 'required|date',
            'profile_image' => 'nullable|image|max:2048',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'guardian_id' => 'nullable|exists:esbtp_parents,id',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();

        try {
            // Mettre à jour l'utilisateur
            $user = $student->user;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            // Gérer l'image de profil
            if ($request->hasFile('profile_image')) {
                // Supprimer l'ancienne image si elle existe
                if ($user->profile_image) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $user->profile_image = $path;
            }
            
            $user->save();

            // Mettre à jour l'étudiant
            $student->update([
                'admission_no' => $request->admission_no,
                'roll_no' => $request->roll_no,
                'class_id' => $request->class_id,
                'section_id' => $request->section_id,
                'session_id' => $request->session_id,
                'father_name' => $request->father_name,
                'mother_name' => $request->mother_name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'pincode' => $request->pincode,
                'admission_date' => $request->admission_date,
                'guardian_id' => $request->guardian_id,
            ]);

            DB::commit();

            return redirect()->route('students.index')
                ->with('success', 'Étudiant mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de l\'étudiant: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified student from storage.
     */
    public function destroy(Student $student)
    {
        DB::beginTransaction();

        try {
            $user = $student->user;
            
            // Supprimer l'image de profil si elle existe
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
            
            // Supprimer l'étudiant (cela supprimera également l'utilisateur grâce à la contrainte onDelete cascade)
            $student->delete();
            
            DB::commit();

            return redirect()->route('students.index')
                ->with('success', 'Étudiant supprimé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression de l\'étudiant: ' . $e->getMessage());
        }
    }

    /**
     * Display the student's grades.
     */
    public function grades(Student $student)
    {
        $student->load(['grades.subject', 'grades.exam', 'grades.semester']);
        
        return view('students.grades', compact('student'));
    }

    /**
     * Display the student's attendance.
     */
    public function attendance(Student $student, Request $request)
    {
        $startDate = $request->start_date ? date('Y-m-d', strtotime($request->start_date)) : date('Y-m-01');
        $endDate = $request->end_date ? date('Y-m-d', strtotime($request->end_date)) : date('Y-m-t');
        
        $attendances = $student->attendances()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();
        
        $attendancePercentage = $student->calculateAttendancePercentage($startDate, $endDate);
        
        return view('students.attendance', compact('student', 'attendances', 'attendancePercentage', 'startDate', 'endDate'));
    }

    /**
     * Display the student's certificates.
     */
    public function certificates(Student $student)
    {
        $student->load('certificates.certificateType');
        
        return view('students.certificates', compact('student'));
    }

    /**
     * Generate a report card for the student.
     */
    public function reportCard(Student $student, Request $request)
    {
        $student->load(['user', 'class', 'section', 'session']);
        
        $semesterId = $request->semester_id;
        $examId = $request->exam_id;
        
        $grades = Grade::with(['subject', 'exam', 'semester'])
            ->where('student_id', $student->id);
        
        if ($semesterId) {
            $grades->where('semester_id', $semesterId);
        }
        
        if ($examId) {
            $grades->where('exam_id', $examId);
        }
        
        $grades = $grades->get();
        
        $averageGrade = $grades->avg('grade_value');
        
        return view('students.report_card', compact('student', 'grades', 'averageGrade'));
    }
} 