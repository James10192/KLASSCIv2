<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTP\FeeCategory;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Http\Request;

class FeeCategoryController extends Controller
{
    public function index()
    {
        $categories = FeeCategory::orderBy('name')->paginate(20);
        return view('esbtp.fees-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('esbtp.fees-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:fee_categories,code',
            'description' => 'nullable|string',
            'default_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        FeeCategory::create($validated);
        return redirect()->route('esbtp.fee-categories.index')->with('success', 'Catégorie de frais créée.');
    }

    public function edit(FeeCategory $fee_category)
    {
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        return view('esbtp.fees-categories.edit', compact('fee_category', 'annees'));
    }

    public function update(Request $request, FeeCategory $fee_category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:fee_categories,code,' . $fee_category->id,
            'description' => 'nullable|string',
            'default_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $fee_category->update($validated);
        return redirect()->route('esbtp.fee-categories.index')->with('success', 'Catégorie de frais mise à jour.');
    }

    public function destroy(FeeCategory $fee_category)
    {
        $fee_category->delete();
        return redirect()->route('esbtp.fee-categories.index')->with('success', 'Catégorie supprimée.');
    }

    public function show(FeeCategory $fee_category)
    {
        $fee_category->load(['rules.filiere', 'rules.niveau', 'rules.anneeUniversitaire']);
        return view('esbtp.fees-categories.show', compact('fee_category'));
    }
}
