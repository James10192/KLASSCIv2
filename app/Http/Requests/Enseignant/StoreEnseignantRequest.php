<?php

namespace App\Http\Requests\Enseignant;

use App\Enums\TeacherRegime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnseignantRequest extends FormRequest
{
    /**
     * Le middleware du groupe ne demande qu'une identite et le module
     * Enseignants : il ne dit pas qui peut creer un compte. C'est ici.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('teachers.create');
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
            // Taux par type de séance (CM/TD/TP) — facturation LMD.
            'taux_par_type' => 'nullable|array',
            'taux_par_type.*' => 'nullable|numeric|min:0',
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
