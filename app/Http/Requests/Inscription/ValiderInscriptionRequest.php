<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Foundation\Http\FormRequest;

class ValiderInscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'montant_paye' => 'nullable|numeric|min:0',
            'observations' => 'nullable|string',
        ];
    }
}
