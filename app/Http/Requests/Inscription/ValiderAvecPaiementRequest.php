<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Foundation\Http\FormRequest;

class ValiderAvecPaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'montant' => 'required|numeric|min:0',
            'fee_category_id' => 'required|exists:esbtp_frais_categories,id',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
            'reference_paiement' => 'nullable|string|max:100',
            'date_paiement' => 'required|date',
            'observations' => 'nullable|string|max:500',
        ];
    }
}
