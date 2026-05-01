<?php

namespace App\Http\Requests\Enseignant;

use App\Enums\TeacherRegime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnseignantRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorisation gérée au niveau du middleware (routes).
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'specialization' => 'required|string|max:255',

            'email' => 'nullable|string|email|max:255|unique:users,email',
            'titre_academique' => 'nullable|string|max:10',
            'grade_academique' => 'nullable|string|max:50',

            'regime' => ['nullable', Rule::in(TeacherRegime::values())],
            'taux_horaire' => 'nullable|numeric|min:0',
            'charge_horaire_max_semaine' => 'nullable|integer|min:1|max:60',
            'date_debut_activite' => 'nullable|date',

            'diplome_principal' => 'nullable|string|max:255',
            'universite_diplome' => 'nullable|string|max:255',
            'annee_diplome' => 'nullable|integer|min:1950|max:' . date('Y'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'specialization.required' => 'La spécialisation est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé par un autre compte.',
            'regime.in' => 'Le régime sélectionné est invalide.',
        ];
    }
}
