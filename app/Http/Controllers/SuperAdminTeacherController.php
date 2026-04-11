<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Department;
use App\Models\Laboratory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class SuperAdminTeacherController extends Controller
{
    /**
     * Constructor to ensure only superAdmin can access these methods
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:superAdmin']);
    }

    /**
     * Display a listing of teachers
     */
    public function index(Request $request)
    {
        $query = User::role('teacher')->with('teacher');

        // Handle search if provided
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Handle status filter if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }

        $teachers = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('esbtp.teachers.index', compact('teachers'));
    }

    /**
     * Show the form for creating a new teacher
     */
    public function create()
    {
        // Get departments and laboratories for the form
        $departments = Department::orderBy('name')->get();
        $laboratories = Laboratory::orderBy('name')->get();

        return view('esbtp.teachers.create', compact('departments', 'laboratories'));
    }

    /**
     * Store a newly created teacher in storage
     */
    public function store(Request $request)
    {
        // Validate user data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            
            // Teacher data validation
            'employee_id' => 'nullable|string|max:50',
            'department_id' => 'required|exists:departments,id',
            'laboratory_id' => 'nullable|exists:laboratories,id',
            'specialties' => 'nullable|string',
            'grade' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'teaching_hours_due' => 'nullable|numeric|min:0',
            'office_location' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'research_interests' => 'nullable|string',
            'website' => 'nullable|url|max:255',
        ]);

        // Process teacher specialties and research interests
        $specialties = !empty($validated['specialties']) ? 
            array_map('trim', explode(',', $validated['specialties'])) : 
            null;
            
        $researchInterests = !empty($validated['research_interests']) ? 
            array_map('trim', explode(',', $validated['research_interests'])) : 
            null;

        try {
            // Use transaction to ensure data integrity
            DB::beginTransaction();

            // Create user
            $user = new User();
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->username = $validated['username'];
            $user->phone = $validated['phone'] ?? null;
            $user->password = Hash::make($validated['password']);
            $user->is_active = true;
            $user->save();

            // Assign teacher role
            $teacherRole = Role::where('name', 'teacher')->first();
            if (!$teacherRole) {
                $teacherRole = Role::create(['name' => 'teacher', 'guard_name' => 'web']);
            }
            $user->assignRole($teacherRole);

            // Create teacher profile
            $teacher = new Teacher();
            $teacher->user_id = $user->id;
            $teacher->employee_id = $validated['employee_id'] ?? null;
            $teacher->department_id = $validated['department_id'];
            $teacher->laboratory_id = $validated['laboratory_id'] ?? null;
            $teacher->specialties = $specialties;
            $teacher->grade = $validated['grade'] ?? null;
            $teacher->status = $validated['status'] ?? null;
            $teacher->teaching_hours_due = $validated['teaching_hours_due'] ?? 0;
            $teacher->teaching_hours_done = 0;
            $teacher->office_location = $validated['office_location'] ?? null;
            $teacher->bio = $validated['bio'] ?? null;
            $teacher->research_interests = $researchInterests;
            $teacher->website = $validated['website'] ?? null;
            $teacher->created_by = auth()->id();
            $teacher->save();

            DB::commit();

            return redirect()->route('esbtp.teachers.index')
                ->with('success', 'Enseignant créé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de l\'enseignant: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified teacher
     */
    public function show(Teacher $teacher)
    {
        $teacher->load('user', 'department', 'laboratory');
        
        return view('esbtp.teachers.show', compact('teacher'));
    }

    /**
     * Show the form for editing the specified teacher
     */
    public function edit(Teacher $teacher)
    {
        $teacher->load('user');
        $departments = Department::orderBy('name')->get();
        $laboratories = Laboratory::orderBy('name')->get();
        
        return view('esbtp.teachers.edit', compact('teacher', 'departments', 'laboratories'));
    }

    /**
     * Update the specified teacher in storage
     */
    public function update(Request $request, Teacher $teacher)
    {
        // Validate user data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $teacher->user_id,
            'username' => 'required|string|max:255|unique:users,username,' . $teacher->user_id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            
            // Teacher data validation
            'employee_id' => 'nullable|string|max:50',
            'department_id' => 'required|exists:departments,id',
            'laboratory_id' => 'nullable|exists:laboratories,id',
            'specialties' => 'nullable|string',
            'grade' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'teaching_hours_due' => 'nullable|numeric|min:0',
            'teaching_hours_done' => 'nullable|numeric|min:0',
            'office_location' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'research_interests' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        // Process teacher specialties and research interests
        $specialties = !empty($validated['specialties']) ? 
            array_map('trim', explode(',', $validated['specialties'])) : 
            null;
            
        $researchInterests = !empty($validated['research_interests']) ? 
            array_map('trim', explode(',', $validated['research_interests'])) : 
            null;

        try {
            // Use transaction to ensure data integrity
            DB::beginTransaction();

            // Update user
            $user = User::findOrFail($teacher->user_id);
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->username = $validated['username'];
            $user->phone = $validated['phone'] ?? null;
            
            // Update password only if provided
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            
            // Update active status if provided
            if (isset($validated['is_active'])) {
                $user->is_active = $validated['is_active'];
            }
            
            $user->save();

            // Ensure user has teacher role
            if (!$user->can('can_teach')) {
                $user->assignRole('teacher');
            }

            // Update teacher profile
            $teacher->employee_id = $validated['employee_id'] ?? null;
            $teacher->department_id = $validated['department_id'];
            $teacher->laboratory_id = $validated['laboratory_id'] ?? null;
            $teacher->specialties = $specialties;
            $teacher->grade = $validated['grade'] ?? null;
            $teacher->status = $validated['status'] ?? null;
            $teacher->teaching_hours_due = $validated['teaching_hours_due'] ?? $teacher->teaching_hours_due;
            $teacher->teaching_hours_done = $validated['teaching_hours_done'] ?? $teacher->teaching_hours_done;
            $teacher->office_location = $validated['office_location'] ?? null;
            $teacher->bio = $validated['bio'] ?? null;
            $teacher->research_interests = $researchInterests;
            $teacher->website = $validated['website'] ?? null;
            $teacher->updated_by = auth()->id();
            $teacher->save();

            DB::commit();

            return redirect()->route('esbtp.teachers.index')
                ->with('success', 'Enseignant mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour de l\'enseignant: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified teacher from storage
     */
    public function destroy(Teacher $teacher)
    {
        try {
            // Use transaction to ensure data integrity
            DB::beginTransaction();
            
            // Get the user ID before deleting the teacher
            $userId = $teacher->user_id;
            
            // Delete the teacher record
            $teacher->delete();
            
            // Find the user
            $user = User::find($userId);
            
            // Remove the teacher role if the user exists
            if ($user) {
                $user->removeRole('teacher');
                
                // Check if the user has any other roles
                if ($user->roles->isEmpty()) {
                    // If no other roles, deactivate the user instead of deleting
                    $user->is_active = false;
                    $user->save();
                }
            }
            
            DB::commit();
            
            return redirect()->route('esbtp.teachers.index')
                ->with('success', 'Enseignant supprimé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de l\'enseignant: ' . $e->getMessage());
        }
    }
} 