<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTP\FeeCategory;
use App\Models\ESBTP\FeeCategoryRule;
use Illuminate\Http\Request;

class FeeCategoryRuleController extends Controller
{
    public function store(Request $request, FeeCategory $fee_category)
    {
        $validated = $request->validate([
            'filiere_id' => 'nullable|exists:esbtp_filieres,id',
            'niveau_id' => 'nullable|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'amount' => 'required|numeric|min:0',
            'payment_schedule' => 'required|in:one_time,monthly,termly,yearly',
            'installments_allowed' => 'required|boolean',
            'min_installment_amount' => 'nullable|numeric|min:0',
            'late_fee' => 'nullable|numeric|min:0',
        ]);
        $fee_category->rules()->create($validated);
        return redirect()->route('esbtp.fee-categories.edit', $fee_category)
            ->with('success', 'Règle de paramétrage ajoutée.');
    }

    public function edit(FeeCategory $fee_category, FeeCategoryRule $rule, Request $request)
    {
        $filieres = \App\Models\ESBTP\ESBTPFiliere::orderBy('name')->get();
        $niveaux = \App\Models\ESBTP\ESBTPNiveauEtude::orderBy('name')->get();
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderByDesc('start_date')->get();
        $alert = null;
        if ($filieres->isEmpty() || $niveaux->isEmpty()) {
            $alert = 'Veuillez d\'abord configurer au moins une filière et un niveau d\'étude.';
        }
        if ($rule->exists === false) {
            if ($request->has('filiere_id')) $rule->filiere_id = $request->get('filiere_id');
            if ($request->has('niveau_id')) $rule->niveau_id = $request->get('niveau_id');
            if ($request->has('annee_universitaire_id')) $rule->annee_universitaire_id = $request->get('annee_universitaire_id');
            if ($request->has('amount')) $rule->amount = $request->get('amount');
            if ($request->has('payment_schedule')) $rule->payment_schedule = $request->get('payment_schedule');
            if ($request->has('installments_allowed')) $rule->installments_allowed = $request->get('installments_allowed');
            if ($request->has('min_installment_amount')) $rule->min_installment_amount = $request->get('min_installment_amount');
            if ($request->has('late_fee')) $rule->late_fee = $request->get('late_fee');
        }
        return view('esbtp.fees-categories.rules.edit', compact('fee_category', 'rule', 'filieres', 'niveaux', 'annees', 'alert'));
    }

    public function update(Request $request, FeeCategory $fee_category, FeeCategoryRule $rule)
    {
        $validated = $request->validate([
            'filiere_id' => 'nullable|exists:esbtp_filieres,id',
            'niveau_id' => 'nullable|exists:esbtp_niveau_etudes,id',
            'amount' => 'required|numeric|min:0',
            'payment_schedule' => 'required|in:one_time,monthly,termly,yearly',
            'installments_allowed' => 'required|boolean',
            'min_installment_amount' => 'nullable|numeric|min:0',
            'late_fee' => 'nullable|numeric|min:0',
        ]);
        $rule->update($validated);
        return redirect()->route('esbtp.fee-categories.edit', $fee_category)
            ->with('success', 'Règle de paramétrage modifiée avec succès.');
    }

    public function destroy(FeeCategory $fee_category, FeeCategoryRule $rule)
    {
        $rule->delete();
        return redirect()->route('esbtp.fee-categories.edit', $fee_category)
            ->with('success', 'Règle de paramétrage supprimée.');
    }
}
