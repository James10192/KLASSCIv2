<?php

namespace App\Http\Requests\Classe;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RemoveStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Defense en profondeur. La route porte desormais la permission, mais ce
        // `true` en etait la seule autre garde : tant qu'il etait la, deplacer la
        // route ou en ajouter une voisine rouvrait le trou sans bruit. N'importe
        // quel compte authentifie, etudiant compris, pouvait modifier la
        // composition d'une classe.
        return $this->user()?->can('classes.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'etudiant_ids' => 'required|array|min:1',
            'etudiant_ids.*' => 'integer|exists:esbtp_etudiants,id',
            'destination_classe_id' => 'nullable|integer|exists:esbtp_classes,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
