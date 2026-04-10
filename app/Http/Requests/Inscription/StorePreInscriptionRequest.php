<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Foundation\Http\FormRequest;

class StorePreInscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'etudiant_existant_id' => 'nullable|integer|exists:esbtp_etudiants,id',
            'nom' => 'required_without:etudiant_existant_id|string|max:100',
            'prenoms' => 'required_without:etudiant_existant_id|string|max:100',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'telephone' => 'nullable|string|max:20',
            'frais' => 'nullable|array',
            'frais.*.variant_id' => 'nullable|string',
            'frais.*.amount' => 'nullable|numeric|min:0',
            'paiement_categories' => 'nullable|array',
            'paiement_categories.*' => 'integer',
            'paiement_montants' => 'nullable|array',
            'paiement_montants.*' => 'numeric|min:0',
            'mode_paiement' => 'nullable|string|in:especes,cheque,virement,mobile_money',
            'reference_paiement' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required_without' => 'Le nom est obligatoire',
            'prenoms.required_without' => 'Le(s) prénom(s) est/sont obligatoire(s)',
            'classe_id.required' => 'Veuillez sélectionner une classe',
        ];
    }
}
