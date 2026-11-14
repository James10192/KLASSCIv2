<?php

namespace App\Http\Requests\Verification;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Recevoir le code de confirmation sur un autre canal, ou a une autre adresse.
 * Voir ChangementCanalVerification.
 */
class ChangerCanalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'demande_id' => ['required', 'uuid'],
            'canal' => ['required', 'in:email,telephone'],
            'email' => ['nullable', 'string', 'email', 'max:254'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $motif = $validator->errors()->has('email') ? 'email_invalide' : 'code_invalide';

        throw new HttpResponseException(response()->json(['envoye' => false, 'motif' => $motif], 422));
    }
}
