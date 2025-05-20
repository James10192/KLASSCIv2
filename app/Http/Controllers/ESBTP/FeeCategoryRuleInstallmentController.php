<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTP\FeeCategoryRule;
use App\Models\ESBTP\FeeCategoryRuleInstallment;
use Illuminate\Http\Request;

class FeeCategoryRuleInstallmentController extends Controller
{
    public function store(Request $request, FeeCategoryRule $rule)
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:100',
            'offset_months' => 'required|integer|min:0',
            'amount' => 'nullable|numeric|min:0',
            'pourcentage' => 'nullable|integer|min:0|max:100',
        ]);
        $validated['offset_days'] = $validated['offset_months'] * 30;
        unset($validated['offset_months']);
        $rule->installments()->create($validated);
        return redirect()->back()->with('success', 'Échéance ajoutée avec succès.');
    }

    public function edit(FeeCategoryRule $rule, FeeCategoryRuleInstallment $installment)
    {
        return view('esbtp.fees-categories.rules.installments.edit', compact('rule', 'installment'));
    }

    public function update(Request $request, FeeCategoryRule $rule, FeeCategoryRuleInstallment $installment)
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:100',
            'offset_months' => 'required|integer|min:0',
            'amount' => 'nullable|numeric|min:0',
            'pourcentage' => 'nullable|integer|min:0|max:100',
        ]);
        $validated['offset_days'] = $validated['offset_months'] * 30;
        unset($validated['offset_months']);
        $installment->update($validated);
        return redirect()->route('esbtp.fee-categories.rules.edit', [$rule->category, $rule])
            ->with('success', 'Échéance modifiée avec succès.');
    }

    public function destroy(FeeCategoryRule $rule, FeeCategoryRuleInstallment $installment)
    {
        $installment->delete();
        return redirect()->back()->with('success', 'Échéance supprimée.');
    }
}
