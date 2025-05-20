<?php

namespace App\Http\Controllers;

use App\Models\ESBTPCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ESBTPCycleController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view cycles')->only(['index', 'show']);
        $this->middleware('permission:create cycles')->only(['create', 'store']);
        $this->middleware('permission:edit cycles')->only(['edit', 'update']);
        $this->middleware('permission:delete cycles')->only('destroy');
        $this->middleware('permission:restore cycles')->only('restore');
        $this->middleware('permission:force delete cycles')->only('forceDelete');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $activeCycles = ESBTPCycle::where('is_active', true)->orderBy('name')->get();
            $inactiveCycles = ESBTPCycle::where('is_active', false)->whereNull('deleted_at')->orderBy('name')->get();
            $archivedCycles = ESBTPCycle::onlyTrashed()->orderBy('name')->get();

            return view('esbtp.cycles.index', compact('activeCycles', 'inactiveCycles', 'archivedCycles'));
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des cycles : ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la récupération des cycles.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('esbtp.cycles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:esbtp_cycles,code',
                'duration_years' => 'required|integer|min:1|max:10',
                'diploma_awarded' => 'required|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean'
            ]);

            $validated['is_active'] = $request->has('is_active');

            DB::beginTransaction();
            $cycle = ESBTPCycle::create($validated);
            DB::commit();

            return redirect()->route('esbtp.cycles.show', $cycle->id)
                ->with('success', 'Le cycle a été créé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du cycle : ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la création du cycle.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ESBTPCycle $cycle)
    {
        return view('esbtp.cycles.show', compact('cycle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ESBTPCycle $cycle)
    {
        return view('esbtp.cycles.edit', compact('cycle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ESBTPCycle $cycle)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:esbtp_cycles,code,' . $cycle->id,
                'duration_years' => 'required|integer|min:1|max:10',
                'diploma_awarded' => 'required|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean'
            ]);

            $validated['is_active'] = $request->has('is_active');

            DB::beginTransaction();
            $cycle->update($validated);
            DB::commit();

            return redirect()->route('esbtp.cycles.show', $cycle->id)
                ->with('success', 'Le cycle a été mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour du cycle : ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du cycle.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ESBTPCycle $cycle)
    {
        try {
            DB::beginTransaction();
            $cycle->delete();
            DB::commit();

            return redirect()->route('esbtp.cycles.index')
                ->with('success', 'Le cycle a été archivé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'archivage du cycle : ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'archivage du cycle.');
        }
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore($id)
    {
        try {
            DB::beginTransaction();
            ESBTPCycle::withTrashed()->findOrFail($id)->restore();
            DB::commit();

            return redirect()->route('esbtp.cycles.index')
                ->with('success', 'Le cycle a été restauré avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la restauration du cycle : ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la restauration du cycle.');
        }
    }

    /**
     * Force delete the specified resource from storage.
     */
    public function forceDelete($id)
    {
        try {
            DB::beginTransaction();
            ESBTPCycle::withTrashed()->findOrFail($id)->forceDelete();
            DB::commit();

            return redirect()->route('esbtp.cycles.index')
                ->with('success', 'Le cycle a été supprimé définitivement avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression définitive du cycle : ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression définitive du cycle.');
        }
    }
}
