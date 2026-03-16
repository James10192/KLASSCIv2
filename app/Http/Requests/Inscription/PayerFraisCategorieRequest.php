<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Foundation\Http\FormRequest;

class PayerFraisCategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'frais_category_id' => 'required|exists:esbtp_frais_categories,id',
            'montant' => 'required|numeric|min:0',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
            'reference_paiement' => 'nullable|string|max:100',
            'date_paiement' => 'required|date',
            'commentaire' => 'nullable|string|max:500',
        ];
    }
}
