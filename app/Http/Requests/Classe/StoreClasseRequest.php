<?php

namespace App\Http\Requests\Classe;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_classes,code',
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_etude_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'places_totales' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'parcours_id' => 'nullable|exists:esbtp_lmd_parcours,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->ajax() || $this->input('is_ajax') === '1') {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
