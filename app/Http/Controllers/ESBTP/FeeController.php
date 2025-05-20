<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTP\Fee;
use App\Models\ESBTPClasse;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTP\FeeCategory;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function index()
    {
        $fees = Fee::with(['class', 'academicYear'])->latest()->get();
        return view('esbtp.fees.index', compact('fees'));
    }

    public function create()
    {
        $categories = FeeCategory::where('is_active', true)->orderBy('name')->get();
        $inscriptions = \App\Models\ESBTPInscription::with('etudiant')->orderByDesc('id')->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('name')->get();
        return view('esbtp.fees.create', compact('categories', 'inscriptions', 'annees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inscription_id' => 'required|exists:esbtp_inscriptions,id',
            'fee_category_id' => 'required|exists:fee_categories,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        // Récupérer la classe à partir de l'inscription
        $inscription = \App\Models\ESBTPInscription::find($validated['inscription_id']);
        $validated['class_id'] = $inscription ? $inscription->classe_id : null;
        $validated['academic_year_id'] = $validated['annee_universitaire_id'];

        Fee::create($validated);

        return redirect()->route('esbtp.fees.index')
            ->with('success', 'Le paiement de frais a été enregistré avec succès.');
    }

    public function show(Fee $fee)
    {
        return view('esbtp.fees.show', compact('fee'));
    }

    public function edit(Fee $fee)
    {
        $categories = FeeCategory::where('is_active', true)->orderBy('name')->get();
        $inscriptions = \App\Models\ESBTPInscription::with('etudiant')->orderByDesc('id')->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('name')->get();
        return view('esbtp.fees.edit', compact('fee', 'categories', 'inscriptions', 'annees'));
    }

    public function update(Request $request, Fee $fee)
    {
        $validated = $request->validate([
            'inscription_id' => 'required|exists:esbtp_inscriptions,id',
            'fee_category_id' => 'required|exists:fee_categories,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $fee->update($validated);

        return redirect()->route('esbtp.fees.index')
            ->with('success', 'Le paiement de frais a été mis à jour avec succès.');
    }

    public function destroy(Fee $fee)
    {
        $fee->delete();

        return redirect()->route('esbtp.fees.index')
            ->with('success', 'Les frais de scolarité ont été supprimés avec succès.');
    }
}
