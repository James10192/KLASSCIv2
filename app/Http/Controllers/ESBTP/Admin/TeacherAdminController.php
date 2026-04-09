<?php

namespace App\Http\Controllers\ESBTP\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPDepartment;
use App\Models\ESBTPLaboratory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use App\Models\Teacher;

class TeacherAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:superAdmin']);
    }

    private function getAvailableStatuses()
    {
        return [
            'permanent' => 'Permanent',
            'vacataire' => 'Vacataire',
            'ater' => 'ATER',
        ];
    }

    public function index()
    {
        $teachers = User::role(['teacher', 'enseignant'])
            ->with('teacher')
            ->latest()
            ->paginate(10);

        return view('esbtp.teachers.index', compact('teachers'));
    }

    public function create()
    {
        $departments = ESBTPDepartment::where('is_active', true)->orderBy('name')->get();
        $laboratories = ESBTPLaboratory::where('is_active', true)->orderBy('name')->get();
        $statuses = $this->getAvailableStatuses();

        return view('esbtp.teachers.create', compact('departments', 'laboratories', 'statuses'));
    }

    public function store(Request $request)
    {
        try {
            // Log the incoming request data
            \Log::info('Teacher creation request data:', $request->all());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'matricule' => 'required|string|max:255|unique:esbtp_teachers',
                'status' => 'required|string|in:' . implode(',', array_keys($this->getAvailableStatuses())),
            'teaching_hours_due' => 'required|numeric|min:0',
            'title' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
                'bio' => 'nullable|string',
                'research_interests' => 'nullable|string',
                'website' => 'nullable|string|url',
                'department_id' => 'nullable|exists:esbtp_departments,id',
                'laboratory_id' => 'nullable|exists:esbtp_laboratories,id',
                'grade' => 'nullable|string|max:255',
                'office_location' => 'nullable|string|max:255',
            ]);

            \Log::info('Validation passed. Creating user...');

        DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
            ]);

            \Log::info('User created successfully with ID: ' . $user->id);

            $user->assignRole('teacher');
            \Log::info('Teacher role assigned to user');

            $teacherData = array_merge(
                array_intersect_key($validated, array_flip([
                    'matricule', 'status', 'teaching_hours_due', 'title',
                    'specialization', 'phone', 'email', 'address', 'city',
                    'country', 'postal_code', 'bio', 'research_interests',
                    'website', 'department_id', 'laboratory_id', 'grade',
                    'office_location'
                ])),
                [
                'user_id' => $user->id,
                'created_by' => auth()->id(),
                ]
            );

            \Log::info('Creating teacher record with data:', $teacherData);

            $teacher = ESBTPTeacher::create($teacherData);

            \Log::info('Teacher record created successfully with ID: ' . $teacher->id);

            DB::commit();
            \Log::info('Transaction committed successfully');

            return redirect()->route('esbtp.teachers.index')
                ->with('success', 'Enseignant créé avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Validation error:', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating teacher:', [
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);
            return back()
                ->with('error', 'Une erreur est survenue lors de la création de l\'enseignant: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(ESBTPTeacher $teacher)
    {
        return view('esbtp.teachers.show', compact('teacher'));
    }

    public function edit(ESBTPTeacher $teacher)
    {
        $departments = ESBTPDepartment::where('is_active', true)->orderBy('name')->get();
        $laboratories = ESBTPLaboratory::where('is_active', true)->orderBy('name')->get();
        $statuses = $this->getAvailableStatuses();
        return view('esbtp.teachers.edit', compact('teacher', 'departments', 'laboratories', 'statuses'));
    }

    public function update(Request $request, ESBTPTeacher $teacher)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($teacher->user_id)],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($teacher->user_id)],
            'matricule' => ['required', 'string', 'max:255', Rule::unique('esbtp_teachers')->ignore($teacher->id)],
            'status' => 'required|string|in:' . implode(',', array_keys($this->getAvailableStatuses())),
            'teaching_hours_due' => 'required|numeric|min:0',
            'title' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
        ]);

        DB::beginTransaction();
        try {
            $user = $teacher->user;
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
            ]);

            if (!empty($validated['password'])) {
                $user->update(['password' => Hash::make($validated['password'])]);
            }

            $teacher->update([
                'matricule' => $validated['matricule'],
                'status' => $validated['status'],
                'teaching_hours_due' => $validated['teaching_hours_due'],
                'title' => $validated['title'],
                'specialization' => $validated['specialization'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'country' => $validated['country'],
                'postal_code' => $validated['postal_code'],
                'updated_by' => auth()->id(),
            ]);

            DB::commit();
            return redirect()->route('esbtp.teachers.index')
                ->with('success', 'Enseignant mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Une erreur est survenue lors de la mise à jour de l\'enseignant.')
                ->withInput();
        }
    }

    public function destroy(ESBTPTeacher $teacher)
    {
        try {
            $teacher->delete();
            $teacher->user->delete();
            return redirect()->route('esbtp.teachers.index')
                ->with('success', 'Enseignant supprimé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Une erreur est survenue lors de la suppression de l\'enseignant.');
        }
    }

    public function restore($id)
    {
        try {
            $teacher = ESBTPTeacher::withTrashed()->findOrFail($id);
            $teacher->restore();
            $teacher->user()->restore();
            return redirect()->route('esbtp.teachers.index')
                ->with('success', 'Enseignant restauré avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Une erreur est survenue lors de la restauration de l\'enseignant.');
        }
    }
}
