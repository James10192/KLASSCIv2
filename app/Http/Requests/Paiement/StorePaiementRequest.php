<?php

namespace App\Http\Requests\Paiement;

use Illuminate\Foundation\Http\FormRequest;

class StorePaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inscription_id' => 'required|exists:esbtp_inscriptions,id',
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'frais_category_id' => 'required|exists:esbtp_frais_categories,id',
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|string',
            'reference_paiement' => 'nullable|string',
            'tranche' => 'nullable|string',
            'commentaire' => 'nullable|string',
        ];
    }
}
