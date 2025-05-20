<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use App\Models\Department;
use App\Models\Laboratory;
use App\Models\Teacher;

class TeacherAdminController extends Controller
{
    /**
     * Constructor with superAdmin role middleware
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:superAdmin']);
    }

    /**
     * Display a listing of teachers
     */
    public function index()
    {
        $teachers = User::role(['teacher', 'enseignant'])->latest()->paginate(10);
        return view('esbtp.teachers.index', compact('teachers'));
    }

    /**
     * Show the form for creating a new teacher
     */
    public function create()
    {
        $departments = Department::all();
        $laboratories = Laboratory::all();
        $statuses = Teacher::getAvailableStatuses();
        return view('esbtp.teachers.create', compact('departments', 'laboratories', 'statuses'));
    }

    /**
     * Store a newly created teacher
     */
    public function store(Request $request)
    {
        // Validation de base pour tous les statuts
        $baseRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'matricule' => 'required|string|max:50|unique:teachers,matricule',
            'phone' => 'required|string|max:20',
            'status' => 'required|string|in:' . implode(',', array_keys(Teacher::getAvailableStatuses())),
            'teaching_hours_due' => 'required|numeric|min:0',
            'specialties' => 'required|string',
        ];

        // Règles supplémentaires selon le statut
        $status = $request->input('status');
        if (!in_array($status, [Teacher::STATUS_VACATAIRE])) {
            $baseRules = array_merge($baseRules, [
                'department_id' => 'required|exists:departments,id',
                'laboratory_id' => 'required|exists:laboratories,id',
                'office_location' => 'required|string',
                'research_interests' => 'required|string',
                'bio' => 'required|string',
            ]);
        }

        // Si ATER, département requis mais pas les autres champs
        if ($status === Teacher::STATUS_ATER) {
            $baseRules['department_id'] = 'required|exists:departments,id';
            unset($baseRules['laboratory_id']);
            unset($baseRules['office_location']);
            unset($baseRules['research_interests']);
            unset($baseRules['bio']);
        }

        $request->validate($baseRules);

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_active' => true,
            ]);

            // Create teacher record with all possible fields
            $teacherData = [
                'matricule' => $request->matricule,
                'status' => $request->status,
                'teaching_hours_due' => $request->teaching_hours_due,
                'specialties' => $request->specialties,
            ];

            // Ajouter les champs optionnels s'ils sont fournis
            $optionalFields = [
                'department_id',
                'laboratory_id',
                'office_location',
                'research_interests',
                'bio',
                'website',
                'grade',
            ];

            foreach ($optionalFields as $field) {
                if ($request->filled($field)) {
                    $teacherData[$field] = $request->$field;
                }
            }

            $user->teacher()->create($teacherData);

            // Assign teacher role
            $role = Role::where('name', 'teacher')->first();
            if (!$role) {
                $role = Role::create(['name' => 'teacher']);
            }
            $user->assignRole($role);

            DB::commit();

            return redirect()
                ->route('esbtp.teachers.index')
                ->with('success', 'Enseignant créé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création de l\'enseignant: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified teacher
     */
    public function show($id)
    {
        $teacher = User::role(['teacher', 'enseignant'])->findOrFail($id);
        return view('esbtp.teachers.show', compact('teacher'));
    }

    /**
     * Show the form for editing the specified teacher
     */
    public function edit($id)
    {
        $teacher = User::role(['teacher', 'enseignant'])->findOrFail($id);
        return view('esbtp.teachers.edit', compact('teacher'));
    }

    /**
     * Update the specified teacher
     */
    public function update(Request $request, $id)
    {
        $teacher = User::role(['teacher', 'enseignant'])->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($teacher->id),
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($teacher->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $teacher->name = $request->name;
            $teacher->email = $request->email;
            $teacher->username = $request->username;

            if ($request->filled('password')) {
                $teacher->password = Hash::make($request->password);
            }

            $teacher->save();

            DB::commit();

            return redirect()
                ->route('esbtp.teachers.index')
                ->with('success', 'Enseignant mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de l\'enseignant: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified teacher
     */
    public function destroy($id)
    {
        $teacher = User::role(['teacher', 'enseignant'])->findOrFail($id);

        DB::beginTransaction();
        try {
            // Remove roles first
            $teacher->roles()->detach();
            // Delete the user
            $teacher->delete();

            DB::commit();

            return redirect()
                ->route('esbtp.teachers.index')
                ->with('success', 'Enseignant supprimé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression de l\'enseignant: ' . $e->getMessage());
        }
    }
}
