<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SuperAdminTeacherController extends Controller
{
    /**
     * Display a listing of the teachers.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Teacher::with(['department', 'designation', 'user']);

        // Filtrage par département
        if ($request->has('department_id') && $request->department_id != '') {
            $query->where('department_id', $request->department_id);
        }

        // Filtrage par statut
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Recherche par nom ou email
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $teachers = $query->latest()->paginate(10);
        $departments = Department::orderBy('name')->get();

        return view('superadmin.teachers.index', compact('teachers', 'departments'));
    }

    /**
     * Show the form for creating a new teacher.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $designations = Designation::where('status', 'active')->orderBy('name')->get();
        
        return view('superadmin.teachers.create', compact('departments', 'designations'));
    }

    /**
     * Store a newly created teacher in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:15',
            'address' => 'nullable|string|max:500',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'joining_date' => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'employee_id' => 'required|string|max:50|unique:teachers',
            'qualification' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'bio' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Création de l'utilisateur
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make('password'), // Mot de passe par défaut
                'role' => 'teacher',
            ]);
            
            // Traitement de l'image
            $profilePicturePath = null;
            if ($request->hasFile('profile_picture')) {
                $profilePicturePath = $request->file('profile_picture')->store('teachers', 'public');
            }
            
            // Création de l'enseignant
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'department_id' => $validated['department_id'],
                'designation_id' => $validated['designation_id'],
                'employee_id' => $validated['employee_id'],
                'phone' => $validated['phone'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'address' => $validated['address'],
                'joining_date' => $validated['joining_date'],
                'qualification' => $validated['qualification'],
                'experience' => $validated['experience'],
                'salary' => $validated['salary'],
                'bio' => $validated['bio'],
                'profile_picture' => $profilePicturePath,
                'status' => $validated['status'],
            ]);
            
            DB::commit();
            
            return redirect()->route('superadmin.teachers.index')
                ->with('success', 'Enseignant créé avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de la création de l\'enseignant: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified teacher.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $teacher = Teacher::with(['department', 'designation', 'user'])->findOrFail($id);
        
        // Récupérer les matières enseignées
        $subjects = $teacher->subjects()->with('class')->get();
        
        return view('superadmin.teachers.show', compact('teacher', 'subjects'));
    }

    /**
     * Show the form for editing the specified teacher.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $teacher = Teacher::with('user')->findOrFail($id);
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $designations = Designation::where('status', 'active')->orderBy('name')->get();
        
        return view('superadmin.teachers.edit', compact('teacher', 'departments', 'designations'));
    }

    /**
     * Update the specified teacher in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $teacher = Teacher::with('user')->findOrFail($id);
        
        // Validation des données
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($teacher->user_id),
            ],
            'phone' => 'required|string|max:15',
            'address' => 'nullable|string|max:500',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'joining_date' => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'employee_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('teachers')->ignore($id),
            ],
            'qualification' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'bio' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Mise à jour de l'utilisateur
            $teacher->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);
            
            // Traitement de l'image
            if ($request->hasFile('profile_picture')) {
                // Supprimer l'ancienne image
                if ($teacher->profile_picture) {
                    Storage::disk('public')->delete($teacher->profile_picture);
                }
                $profilePicturePath = $request->file('profile_picture')->store('teachers', 'public');
            } else {
                $profilePicturePath = $teacher->profile_picture;
            }
            
            // Mise à jour de l'enseignant
            $teacher->update([
                'department_id' => $validated['department_id'],
                'designation_id' => $validated['designation_id'],
                'employee_id' => $validated['employee_id'],
                'phone' => $validated['phone'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'address' => $validated['address'],
                'joining_date' => $validated['joining_date'],
                'qualification' => $validated['qualification'],
                'experience' => $validated['experience'],
                'salary' => $validated['salary'],
                'bio' => $validated['bio'],
                'profile_picture' => $profilePicturePath,
                'status' => $validated['status'],
            ]);
            
            DB::commit();
            
            return redirect()->route('superadmin.teachers.index')
                ->with('success', 'Enseignant mis à jour avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de l\'enseignant: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified teacher from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        
        try {
            DB::beginTransaction();
            
            // Supprimer l'image
            if ($teacher->profile_picture) {
                Storage::disk('public')->delete($teacher->profile_picture);
            }
            
            // Supprimer l'enseignant
            $teacher->delete();
            
            // Supprimer l'utilisateur associé
            $teacher->user->delete();
            
            DB::commit();
            
            return redirect()->route('superadmin.teachers.index')
                ->with('success', 'Enseignant supprimé avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->with('error', 'Une erreur est survenue lors de la suppression de l\'enseignant: ' . $e->getMessage());
        }
    }
    
    /**
     * Reset the teacher's password.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPassword($id)
    {
        $teacher = Teacher::with('user')->findOrFail($id);
        
        try {
            // Réinitialiser le mot de passe à 'password'
            $teacher->user->update([
                'password' => Hash::make('password'),
            ]);
            
            return redirect()->route('superadmin.teachers.show', $id)
                ->with('success', 'Mot de passe réinitialisé avec succès à "password".');
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Une erreur est survenue lors de la réinitialisation du mot de passe: ' . $e->getMessage());
        }
    }
} 