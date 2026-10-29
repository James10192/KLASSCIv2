<?php

namespace App\Http\Requests\Paiement;

use Illuminate\Contracts\Validation\Validator;
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
            'confirmed_zero_amount' => 'nullable|in:0,1',
        ];
    }

    /**
     * Audit 2026-06-04 §2.13 : garde-fou montant=0 (cas exonération uniquement).
     * Cohérent avec StorePaiementRequest.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $montant = (int) $this->input('montant', 0);
            $confirmedZero = $this->input('confirmed_zero_amount') === '1' || $this->input('confirmed_zero_amount') === 1;
            if ($montant === 0 && !$confirmedZero) {
                $v->errors()->add(
                    'montant',
                    'Montant à 0 FCFA détecté. Ce paiement ne sera enregistré que si vous confirmez qu\'il s\'agit d\'une exonération (cochez la case dédiée).',
                );
            }
        });
    }
}
