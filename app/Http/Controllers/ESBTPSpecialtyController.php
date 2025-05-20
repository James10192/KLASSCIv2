<?php

namespace App\Http\Controllers;

use App\Models\ESBTPSpecialty;
use App\Models\ESBTPDepartment;
use App\Models\ESBTPCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ESBTPSpecialtyController extends Controller
{
    public function index()
    {
        $activeSpecialties = ESBTPSpecialty::where('is_active', true)->get();
        $inactiveSpecialties = ESBTPSpecialty::where('is_active', false)->get();
        $archivedSpecialties = ESBTPSpecialty::onlyTrashed()->get();

        return view('esbtp.specialties.index', compact('activeSpecialties', 'inactiveSpecialties', 'archivedSpecialties'));
    }

    public function create()
    {
        $departments = ESBTPDepartment::all();
        $cycles = ESBTPCycle::all();
        return view('esbtp.specialties.create', compact('departments', 'cycles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_specialties',
            'department_id' => 'required|exists:esbtp_departments,id',
            'cycle_id' => 'required|exists:esbtp_cycles,id',
            'coordinator_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'career_opportunities' => 'nullable|string',
        ]);

        ESBTPSpecialty::create($validated);

        return redirect()->route('esbtp.specialties.index')
            ->with('success', 'Spécialité créée avec succès.');
    }

    public function show(ESBTPSpecialty $specialty)
    {
        return view('esbtp.specialties.show', compact('specialty'));
    }

    public function edit(ESBTPSpecialty $specialty)
    {
        $departments = ESBTPDepartment::all();
        $cycles = ESBTPCycle::all();
        return view('esbtp.specialties.edit', compact('specialty', 'departments', 'cycles'));
    }

    public function update(Request $request, ESBTPSpecialty $specialty)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_specialties,code,' . $specialty->id,
            'department_id' => 'required|exists:esbtp_departments,id',
            'cycle_id' => 'required|exists:esbtp_cycles,id',
            'coordinator_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'career_opportunities' => 'nullable|string',
        ]);

        $specialty->update($validated);

        return redirect()->route('esbtp.specialties.show', $specialty)
            ->with('success', 'Spécialité mise à jour avec succès.');
    }

    public function destroy(ESBTPSpecialty $specialty)
    {
        $specialty->delete();
        return redirect()->route('esbtp.specialties.index')
            ->with('success', 'Spécialité archivée avec succès.');
    }

    public function restore($id)
    {
        $specialty = ESBTPSpecialty::withTrashed()->findOrFail($id);
        $specialty->restore();
        return redirect()->route('esbtp.specialties.index')
            ->with('success', 'Spécialité restaurée avec succès.');
    }
}
