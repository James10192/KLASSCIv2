<?php

namespace App\Http\Controllers;

use App\Models\ESBTPDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ESBTPDepartmentController extends Controller
{
    public function index()
    {
        $activeDepartments = ESBTPDepartment::where('is_active', true)->get();
        $inactiveDepartments = ESBTPDepartment::where('is_active', false)->get();
        $archivedDepartments = ESBTPDepartment::onlyTrashed()->get();

        return view('esbtp.departments.index', compact('activeDepartments', 'inactiveDepartments', 'archivedDepartments'));
    }

    public function create()
    {
        return view('esbtp.departments.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_departments',
            'description' => 'nullable|string',
            'head_name' => 'nullable|string|max:255',
            'head_title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'office_location' => 'nullable|string|max:255',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        ESBTPDepartment::create($validated);

        return redirect()->route('esbtp.departments.index')
            ->with('success', 'Département créé avec succès.');
    }

    public function show(ESBTPDepartment $department)
    {
        return view('esbtp.departments.show', compact('department'));
    }

    public function edit(ESBTPDepartment $department)
    {
        return view('esbtp.departments.edit', compact('department'));
    }

    public function update(Request $request, ESBTPDepartment $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_departments,code,' . $department->id,
            'description' => 'nullable|string',
            'head_name' => 'nullable|string|max:255',
            'head_title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'office_location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        $department->update($validated);

        return redirect()->route('esbtp.departments.index')
            ->with('success', 'Département mis à jour avec succès.');
    }

    public function destroy(ESBTPDepartment $department)
    {
        $department->delete();

        return redirect()->route('esbtp.departments.index')
            ->with('success', 'Département supprimé avec succès.');
    }

    public function restore($id)
    {
        $department = ESBTPDepartment::withTrashed()->findOrFail($id);
        $department->restore();

        return redirect()->route('esbtp.departments.index')
            ->with('success', 'Département restauré avec succès.');
    }

    public function forceDelete($id)
    {
        $department = ESBTPDepartment::withTrashed()->findOrFail($id);
        $department->forceDelete();

        return redirect()->route('esbtp.departments.index')
            ->with('success', 'Département supprimé définitivement avec succès.');
    }
}
