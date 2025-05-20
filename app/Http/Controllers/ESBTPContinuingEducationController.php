<?php

namespace App\Http\Controllers;

use App\Models\ESBTPContinuingEducation;
use App\Models\ESBTPDepartment;
use App\Models\ESBTPCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ESBTPContinuingEducationController extends Controller
{
    public function index()
    {
        $activePrograms = ESBTPContinuingEducation::where('is_active', true)->get();
        $inactivePrograms = ESBTPContinuingEducation::where('is_active', false)->get();
        $archivedPrograms = ESBTPContinuingEducation::onlyTrashed()->get();

        return view('esbtp.continuing-education.index', compact('activePrograms', 'inactivePrograms', 'archivedPrograms'));
    }

    public function create()
    {
        $departments = ESBTPDepartment::all();
        $cycles = ESBTPCycle::all();
        return view('esbtp.continuing-education.create', compact('departments', 'cycles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_continuing_education',
            'department_id' => 'required|exists:esbtp_departments,id',
            'cycle_id' => 'required|exists:esbtp_cycles,id',
            'coordinator_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'required|integer|min:1',
            'duration_unit' => 'required|in:days,weeks,months',
            'price' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'prerequisites' => 'nullable|string',
            'objectives' => 'nullable|string',
            'target_audience' => 'nullable|string',
        ]);

        ESBTPContinuingEducation::create($validated);

        return redirect()->route('esbtp.continuing-education.index')
            ->with('success', 'Programme de formation continue créé avec succès.');
    }

    public function show(ESBTPContinuingEducation $continuingEducation)
    {
        return view('esbtp.continuing-education.show', compact('continuingEducation'));
    }

    public function edit(ESBTPContinuingEducation $continuingEducation)
    {
        $departments = ESBTPDepartment::all();
        $cycles = ESBTPCycle::all();
        return view('esbtp.continuing-education.edit', compact('continuingEducation', 'departments', 'cycles'));
    }

    public function update(Request $request, ESBTPContinuingEducation $continuingEducation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_continuing_education,code,' . $continuingEducation->id,
            'department_id' => 'required|exists:esbtp_departments,id',
            'cycle_id' => 'required|exists:esbtp_cycles,id',
            'coordinator_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'required|integer|min:1',
            'duration_unit' => 'required|in:days,weeks,months',
            'price' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'prerequisites' => 'nullable|string',
            'objectives' => 'nullable|string',
            'target_audience' => 'nullable|string',
        ]);

        $continuingEducation->update($validated);

        return redirect()->route('esbtp.continuing-education.show', $continuingEducation)
            ->with('success', 'Programme de formation continue mis à jour avec succès.');
    }

    public function destroy(ESBTPContinuingEducation $continuingEducation)
    {
        $continuingEducation->delete();
        return redirect()->route('esbtp.continuing-education.index')
            ->with('success', 'Programme de formation continue archivé avec succès.');
    }

    public function restore($id)
    {
        $continuingEducation = ESBTPContinuingEducation::withTrashed()->findOrFail($id);
        $continuingEducation->restore();
        return redirect()->route('esbtp.continuing-education.index')
            ->with('success', 'Programme de formation continue restauré avec succès.');
    }
}
