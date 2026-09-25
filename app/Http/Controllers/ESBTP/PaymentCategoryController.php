<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTP\PaymentCategory;
use App\Support\ListeInfinie;
use Illuminate\Http\Request;

class PaymentCategoryController extends Controller
{
    public function index(Request $request)
    {
        // Departage stable : la liste se charge par tranches.
        $categories = PaymentCategory::orderBy('name')->orderBy('id')->paginate(20);

        if (ListeInfinie::demandee($request)) {
            return ListeInfinie::reponse($categories, fn ($cat) => view('esbtp.payment-categories._ligne', compact('cat'))->render());
        }
        return view('esbtp.payment-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('esbtp.payment-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_categories,code',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);
        PaymentCategory::create($validated);
        return redirect()->route('esbtp.payment-categories.index')->with('success', 'Catégorie créée.');
    }

    public function edit(PaymentCategory $payment_category)
    {
        return view('esbtp.payment-categories.edit', compact('payment_category'));
    }

    public function update(Request $request, PaymentCategory $payment_category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_categories,code,' . $payment_category->id,
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);
        $payment_category->update($validated);
        return redirect()->route('esbtp.payment-categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(PaymentCategory $payment_category)
    {
        $payment_category->delete();
        return redirect()->route('esbtp.payment-categories.index')->with('success', 'Catégorie supprimée.');
    }
}
