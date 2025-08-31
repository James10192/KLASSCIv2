<?php

namespace App\Http\Controllers;

use App\Models\ESBTPDepense;
use App\Models\ESBTPCategorieDepense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DepensesController extends Controller
{
    /**
     * Display a listing of the expenses.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $depenses = ESBTPDepense::with('categorie')
            ->orderBy('date_depense', 'desc')
            ->paginate(10);

        $categories = ESBTPCategorieDepense::all();

        return view('esbtp.comptabilite.depenses.index', compact('depenses', 'categories'));
    }

    /**
     * Display the form for creating a new expense.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = ESBTPCategorieDepense::where('est_actif', true)->orderBy('nom')->get();
        return view('esbtp.comptabilite.depenses.create', compact('categories'));
    }

    /**
     * Store a newly created expense in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'libelle' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'date_depense' => 'required|date',
            'categorie_id' => 'required|exists:esbtp_categories_depenses,id',
            'description' => 'nullable|string',
            'mode_paiement' => 'required|string',
            'reference' => 'nullable|string|unique:esbtp_depenses,reference',
        ]);

        $validated['createur_id'] = Auth::id();
        $validated['statut'] = 'validée';

        ESBTPDepense::create($validated);

        return redirect()->route('esbtp.comptabilite.depenses')
            ->with('success', 'Dépense enregistrée avec succès.');
    }

    /**
     * Display the specified expense.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $depense = ESBTPDepense::with('categorie', 'createur')->findOrFail($id);
        return view('esbtp.comptabilite.depenses.show', compact('depense'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $depense = ESBTPDepense::findOrFail($id);
        $categories = ESBTPCategorieDepense::where('est_actif', true)->orderBy('name')->get();

        return view('esbtp.comptabilite.depenses.edit', compact('depense', 'categories'));
    }

    /**
     * Update the specified expense in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $depense = ESBTPDepense::findOrFail($id);

        $validated = $request->validate([
            'libelle' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'date_depense' => 'required|date',
            'categorie_id' => 'required|exists:esbtp_categories_depenses,id',
            'description' => 'nullable|string',
            'mode_paiement' => 'required|string',
            'reference' => 'nullable|string|unique:esbtp_depenses,reference,' . $id,
        ]);

        $depense->update($validated);

        return redirect()->route('esbtp.comptabilite.depenses')
            ->with('success', 'Dépense mise à jour avec succès.');
    }

    /**
     * Remove the specified expense from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $depense = ESBTPDepense::findOrFail($id);
        $depense->delete();

        return redirect()->route('esbtp.comptabilite.depenses')
            ->with('success', 'Dépense supprimée avec succès.');
    }

    /**
     * Display the expense categories management page.
     *
     * @return \Illuminate\Http\Response
     */
    public function categories()
    {
        $categories = ESBTPCategorieDepense::withCount('depenses')
            ->orderBy('nom')
            ->get();

        return view('esbtp.comptabilite.depenses.categories', compact('categories'));
    }

    /**
     * Store a new expense category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:esbtp_categories_depenses,nom',
            'description' => 'nullable|string',
            'code' => 'required|string|unique:esbtp_categories_depenses,code',
        ]);

        ESBTPCategorieDepense::create($validated);

        return redirect()->route('esbtp.comptabilite.depenses.categories')
            ->with('success', 'Catégorie de dépense créée avec succès.');
    }

    /**
     * Update an expense category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateCategory(Request $request, $id)
    {
        $category = ESBTPCategorieDepense::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:esbtp_categories_depenses,nom,'.$id,
            'description' => 'nullable|string',
            'code' => 'required|string|unique:esbtp_categories_depenses,code,'.$id,
        ]);

        $category->update($validated);

        return redirect()->route('esbtp.comptabilite.depenses.categories')
            ->with('success', 'Catégorie de dépense mise à jour avec succès.');
    }

    /**
     * Delete an expense category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyCategory($id)
    {
        $category = ESBTPCategorieDepense::findOrFail($id);

        // Check if the category has expenses
        if ($category->depenses()->count() > 0) {
            return redirect()->route('esbtp.comptabilite.depenses.categories')
                ->with('error', 'Impossible de supprimer cette catégorie car elle est associée à des dépenses.');
        }

        $category->delete();

        return redirect()->route('esbtp.comptabilite.depenses.categories')
            ->with('success', 'Catégorie de dépense supprimée avec succès.');
    }
}
