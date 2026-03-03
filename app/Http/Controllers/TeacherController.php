<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Subject;
use App\Models\TeacherAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Laboratory;
// App\....\Laboratory
class TeacherController extends Controller
{
    /**
     * Display a listing of the teachers.
     */
    public function index(Request $request)
    {
        $query = Teacher::with(['user', 'department', 'designation']);

        // Filtres
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->designation_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('employee_id', 'like', "%{$search}%");
        }

        $teachers = $query->paginate(15);
        $departments = Department::all();
        $designations = Designation::all();

        return view('teachers.index', compact('teachers', 'departments', 'designations'));
    }

    /**
     * Show the form for creating a new teacher.
     */
    public function create()
{
    $departments = Department::all();
    $designations = Designation::all();
    $subjects = Subject::all();
    $laboratories = Laboratory::all(); // Ajout ici

    return view('teachers.create', compact('departments', 'designations', 'subjects', 'laboratories'));
}
    // public function create()
    // {
    //     $departments = Department::all();
    //     $designations = Designation::all();
    //     $subjects = Subject::all();

    //     return view('teachers.create', compact('departments', 'designations', 'subjects'));
    // }

    /**
     * Store a newly created teacher in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'employee_id' => 'required|string|max:255|unique:teachers',
            'qualification' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'joining_date' => 'required|date',
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'profile_image' => 'nullable|image|max:2048',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'marital_status' => 'nullable|string|max:20',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
            'laboratory_id' => 'nullable|exists:laboratories,id'

        ]);

        DB::beginTransaction();

        try {
            // Créer l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'teacher',
                'is_active' => true,
                'phone' => $request->phone,
            ]);

            // Attribution du rôle Spatie
            $user->assignRole('enseignant');

            // Gérer l'image de profil
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $user->profile_image = $path;
                $user->save();
            }

            // Créer l'enseignant
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'employee_id' => $request->employee_id,
                'qualification' => $request->qualification,
                'experience' => $request->experience,
                'joining_date' => $request->joining_date,
                'department_id' => $request->department_id,
                'designation_id' => $request->designation_id,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'pincode' => $request->pincode,
                'emergency_contact' => $request->emergency_contact,
                'marital_status' => $request->marital_status,
                'laboratory_id' => $request->laboratory_id,
            ]);

            // Associer les matières à l'enseignant
            if ($request->has('subjects')) {
                $teacher->subjects()->attach($request->subjects);
            }

            DB::commit();

            return redirect()->route('teachers.index')
                ->with('success', 'Enseignant créé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création de l\'enseignant: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified teacher.
     */
    public function show(Teacher $teacher)
    {
        $teacher->load(['user', 'department', 'designation', 'subjects', 'timetableEntries.class', 'timetableEntries.section', 'timetableEntries.subject']);

        // Calculer le taux de présence
        $startDate = now()->subDays(30);
        $endDate = now();
        $attendancePercentage = $teacher->calculateAttendancePercentage($startDate, $endDate);

        return view('teachers.show', compact('teacher', 'attendancePercentage'));
    }

    /**
     * Show the form for editing the specified teacher.
     */
    public function edit(Teacher $teacher)
    {
        $teacher->load(['user', 'subjects']);
        $departments = Department::all();
        $designations = Designation::all();
        $subjects = Subject::all();

        return view('teachers.edit', compact('teacher', 'departments', 'designations', 'subjects'));
    }

    /**
     * Update the specified teacher in storage.
     */
    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($teacher->user_id),
            ],
            'employee_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teachers')->ignore($teacher->id),
            ],
            'qualification' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'joining_date' => 'required|date',
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'profile_image' => 'nullable|image|max:2048',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'marital_status' => 'nullable|string|max:20',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
        ]);

        DB::beginTransaction();

        try {
            // Mettre à jour l'utilisateur
            $user = $teacher->user;
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

            // Mettre à jour l'enseignant
            $teacher->update([
                'employee_id' => $request->employee_id,
                'qualification' => $request->qualification,
                'experience' => $request->experience,
                'joining_date' => $request->joining_date,
                'department_id' => $request->department_id,
                'designation_id' => $request->designation_id,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'pincode' => $request->pincode,
                'emergency_contact' => $request->emergency_contact,
                'marital_status' => $request->marital_status,
            ]);

            // Mettre à jour les matières de l'enseignant
            if ($request->has('subjects')) {
                $teacher->subjects()->sync($request->subjects);
            } else {
                $teacher->subjects()->detach();
            }

            DB::commit();

            return redirect()->route('teachers.index')
                ->with('success', 'Enseignant mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de l\'enseignant: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified teacher from storage.
     */
    public function destroy(Teacher $teacher)
    {
        DB::beginTransaction();

        try {
            $user = $teacher->user;

            // Supprimer l'image de profil si elle existe
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            // Supprimer l'enseignant (cela supprimera également l'utilisateur grâce à la contrainte onDelete cascade)
            $teacher->delete();

            DB::commit();

            return redirect()->route('teachers.index')
                ->with('success', 'Enseignant supprimé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression de l\'enseignant: ' . $e->getMessage());
        }
    }

    /**
     * Display the teacher's timetable.
     */
    public function timetable(Teacher $teacher)
    {
        $teacher->load(['timetableEntries.class', 'timetableEntries.section', 'timetableEntries.subject']);

        $timetable = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $day) {
            $timetable[$day] = $teacher->timetableEntries()->forDay($day)->orderBy('start_time')->get();
        }

        return view('teachers.timetable', compact('teacher', 'timetable', 'days'));
    }

    /**
     * Display the teacher's attendance.
     */
    public function attendance(Teacher $teacher, Request $request)
    {
        $startDate = $request->start_date ? date('Y-m-d', strtotime($request->start_date)) : date('Y-m-01');
        $endDate = $request->end_date ? date('Y-m-d', strtotime($request->end_date)) : date('Y-m-t');

        $attendances = $teacher->attendances()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        $attendancePercentage = $teacher->calculateAttendancePercentage($startDate, $endDate);

        return view('teachers.attendance', compact('teacher', 'attendances', 'attendancePercentage', 'startDate', 'endDate'));
    }

    /**
     * Display the teacher's subjects.
     */
    public function subjects(Teacher $teacher)
    {
        $teacher->load('subjects');

        return view('teachers.subjects', compact('teacher'));
    }

    /**
     * Display the authenticated teacher's profile.
     */
    public function profile()
    {
        // Essayer d'abord ESBTPTeacher, puis Teacher
        $teacher = \App\Models\ESBTPTeacher::where('user_id', Auth::id())->first();

        if (!$teacher) {
            $teacher = Auth::user()->teacher;
        }

        if (!$teacher) {
            // Create a temporary teacher object with user data for display
            $teacher = new \App\Models\ESBTPTeacher();
            $teacher->user = Auth::user();
            $teacher->employee_id = 'N/A';
            $teacher->qualification = null;
            $teacher->experience = null;
            $teacher->joining_date = null;
            $teacher->department = null;
            $teacher->designation = null;
            $teacher->subjects = collect();
        } else {
            // Charger les relations selon le type de teacher
            if ($teacher instanceof \App\Models\ESBTPTeacher) {
                $teacher->load(['user', 'department']);
                // ESBTPTeacher n'a pas de designation ni subjects, les initialiser
                $teacher->designation = null;
                $teacher->subjects = collect();
            } else {
                $teacher->load(['user', 'department', 'designation', 'subjects']);
            }
        }

        return view('teacher.profile', compact('teacher'));
    }

    /**
     * Update the authenticated teacher's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Essayer d'abord ESBTPTeacher, puis Teacher
        $teacher = \App\Models\ESBTPTeacher::where('user_id', $user->id)->first();

        if (!$teacher) {
            $teacher = $user->teacher;
        }

        if (!$teacher) {
            return redirect()->back()->with('error', 'Profil enseignant introuvable.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'qualification' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            // Mettre à jour l'utilisateur
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;

            // Gérer la photo de profil
            if ($request->hasFile('profile_photo')) {
                // Supprimer l'ancienne photo si elle existe
                if ($user->profile_photo_path) {
                    Storage::disk('public')->delete($user->profile_photo_path);
                }

                $path = $request->file('profile_photo')->store('profile-photos', 'public');
                $user->profile_photo_path = $path;
            }

            $user->save();

            // Mettre à jour les informations professionnelles de l'enseignant
            $teacher->update([
                'qualification' => $request->qualification,
                'experience' => $request->experience,
            ]);

            DB::commit();

            return redirect()->route('teacher.profile')
                ->with('success', 'Profil mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du profil: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update the authenticated teacher's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'min:8', 'confirmed'],
        ]);

        Auth::user()->update(['password' => bcrypt($request->password)]);

        return redirect()->route('teacher.profile')->with('success', 'Mot de passe mis à jour avec succès.');
    }
}
