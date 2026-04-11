<?php

namespace App\Http\Requests\Paiement;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|string',
            'reference_paiement' => 'nullable|string',
            'tranche' => 'nullable|string',
            'frais_category_id' => 'required|exists:esbtp_frais_categories,id',
            'commentaire' => 'nullable|string',
        ];
    }
}
