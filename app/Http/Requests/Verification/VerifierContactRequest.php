<?php

namespace App\Http\Requests\Verification;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * `{"canal", "jeton"}` OU `{"canal", "demande_id", "code"}`.
 *
 * Une saisie mal formee rend la reponse d'un code faux : le site vitrine n'a
 * qu'une forme de refus a traiter, et la forme de la reponse ne dit rien.
 */
class VerifierContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'canal' => ['nullable', 'in:email,telephone'],
            'jeton' => ['required_without:demande_id', 'nullable', 'string', 'max:200'],
            'demande_id' => ['required_without:jeton', 'nullable', 'uuid'],
            'code' => ['required_with:demande_id', 'nullable', 'digits:6'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['verifie' => false, 'motif' => 'code_invalide'], 422));
    }
}
