<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPMatiere;
use App\Models\Department;
use App\Models\Laboratory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class ESBTPEnseignantController extends Controller
{
    /**
     * Constructeur avec middleware d'authentification
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superAdmin');
    }

    /**
     * Affiche la liste des enseignants
     */
    public function index()
    {
        $enseignants = ESBTPTeacher::with(['user', 'department', 'laboratory'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('esbtp.enseignants.index', compact('enseignants'));
    }

    /**
     * Affiche le formulaire de création d'un enseignant
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $laboratories = Laboratory::orderBy('name')->get();
        return view('esbtp.enseignants.create', compact('departments', 'laboratories'));
    }

    /**
     * Enregistre un nouvel enseignant
     */
    public function store(Request $request)
    {
        dd($request->all());
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'employee_id' => 'nullable|string|unique:teachers,employee_id',
            'department_id' => 'nullable|exists:departments,id',
            'laboratory_id' => 'nullable|exists:laboratories,id',
            'specialties' => 'nullable|array',
            'grade' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'teaching_hours_due' => 'nullable|integer|min:0',
            'office_location' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'research_interests' => 'nullable|array',
            'website' => 'nullable|url|max:255',
            'availability' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Créer l'utilisateur
            $user = User::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'is_active' => true,
            ]);

            // Créer l'enseignant
            $teacher = ESBTPTeacher::create([
                'user_id' => $user->id,
                'employee_id' => $request->employee_id,
                'department_id' => $request->department_id,
                'laboratory_id' => $request->laboratory_id,
                'specialties' => $request->specialties,
                'grade' => $request->grade,
                'status' => $request->status,
                'teaching_hours_due' => $request->teaching_hours_due ?? 0,
                'teaching_hours_done' => 0,
                'office_location' => $request->office_location,
                'bio' => $request->bio,
                'research_interests' => $request->research_interests,
                'website' => $request->website,
                'availability' => $request->availability,
                'created_by' => auth()->id()
            ]);

            // Assigner le rôle enseignant
            $role = Role::firstOrCreate(['name' => 'enseignant']);
            $user->assignRole($role);

            DB::commit();

            return redirect()->route('esbtp.enseignants.index')
                ->with('success', 'Enseignant créé avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la création de l\'enseignant: ' . $e->getMessage());
        }
    }

    /**
     * Affiche les détails d'un enseignant
     */
    public function show($id)
    {
        $enseignant = ESBTPTeacher::with(['user', 'department', 'laboratory', 'seancesCours'])
            ->findOrFail($id);
        return view('esbtp.enseignants.show', compact('enseignant'));
    }

    /**
     * Affiche le formulaire d'édition d'un enseignant
     */
    public function edit($id)
    {
        $enseignant = ESBTPTeacher::with(['user', 'department', 'laboratory'])
            ->findOrFail($id);
        $departments = Department::orderBy('name')->get();
        $laboratories = Laboratory::orderBy('name')->get();
        return view('esbtp.enseignants.edit', compact('enseignant', 'departments', 'laboratories'));
    }

    /**
     * Met à jour un enseignant
     */
    public function update(Request $request, $id)
    {
        $teacher = ESBTPTeacher::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $teacher->user_id,
            'username' => 'required|string|max:255|unique:users,username,' . $teacher->user_id,
            'employee_id' => 'nullable|string|unique:teachers,employee_id,' . $teacher->id,
            'department_id' => 'nullable|exists:departments,id',
            'laboratory_id' => 'nullable|exists:laboratories,id',
            'specialties' => 'nullable|array',
            'grade' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'teaching_hours_due' => 'nullable|integer|min:0',
            'office_location' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'research_interests' => 'nullable|array',
            'website' => 'nullable|url|max:255',
            'availability' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Mettre à jour l'utilisateur
            $teacher->user->update([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'username' => $request->username,
            ]);

            // Mettre à jour le mot de passe si fourni
            if ($request->filled('password')) {
                $teacher->user->update([
                    'password' => Hash::make($request->password)
                ]);
            }

            // Mettre à jour le profil de l'enseignant
            $teacher->update([
                'employee_id' => $request->employee_id,
                'department_id' => $request->department_id,
                'laboratory_id' => $request->laboratory_id,
                'specialties' => $request->specialties,
                'grade' => $request->grade,
                'status' => $request->status,
                'teaching_hours_due' => $request->teaching_hours_due,
                'office_location' => $request->office_location,
                'bio' => $request->bio,
                'research_interests' => $request->research_interests,
                'website' => $request->website,
                'availability' => $request->availability,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return redirect()->route('esbtp.enseignants.index')
                ->with('success', 'Enseignant mis à jour avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de l\'enseignant: ' . $e->getMessage());
        }
    }

    /**
     * Supprime un enseignant
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $teacher = ESBTPTeacher::findOrFail($id);

            // Store user_id before deleting teacher
            $userId = $teacher->user_id;

            // Delete teacher record
            $teacher->delete();

            // Find and update user
            $user = User::find($userId);
            if ($user) {
                $user->removeRole('enseignant');

                // If user has no other roles, deactivate instead of deleting
                if ($user->roles->isEmpty()) {
                    $user->update(['is_active' => false]);
                }
            }

            DB::commit();

            return redirect()->route('esbtp.enseignants.index')
                ->with('success', 'Enseignant supprimé avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression de l\'enseignant: ' . $e->getMessage());
        }
    }

    /**
     * Promouvoir un enseignant au rang de Super Admin
     */
    public function promoteToAdmin($id)
    {
        $enseignant = User::role('enseignant')->findOrFail($id);

        // Vérifier si l'enseignant a déjà le rôle de superAdmin
        if ($enseignant->hasRole('superAdmin')) {
            return redirect()->back()->with('warning', 'Cet enseignant est déjà un Super Admin.');
        }

        // Attribuer le rôle de superAdmin tout en conservant le rôle d'enseignant
        $enseignant->assignRole('superAdmin');

        return redirect()->route('esbtp.enseignants.index')
            ->with('success', 'L\'enseignant a été promu au rang de Super Admin avec succès.');
    }

    /**
     * Rétrograder un Super Admin-Enseignant au rang d'enseignant simple
     */
    public function demoteFromAdmin($id)
    {
        $enseignant = User::role('enseignant')->findOrFail($id);

        // Vérifier si l'enseignant a le rôle de superAdmin
        if (!$enseignant->hasRole('superAdmin')) {
            return redirect()->back()->with('warning', 'Cet enseignant n\'est pas un Super Admin.');
        }

        // Retirer le rôle de superAdmin
        $enseignant->removeRole('superAdmin');

        return redirect()->route('esbtp.enseignants.index')
            ->with('success', 'L\'enseignant a été rétrogradé avec succès.');
    }
}
