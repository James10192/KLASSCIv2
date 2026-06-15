<?php

namespace App\Http\Requests\Enseignant;

use App\Enums\TeacherRegime;
use App\Enums\TeacherStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnseignantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // L'enseignant est résolu via route model binding (paramètre {enseignant}).
        $enseignant = $this->route('enseignant');
        $userId = $enseignant?->user_id;

        return [
            'name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',

            // Téléphone nullable : enseignants legacy sans téléphone autorisés.
            'phone' => 'nullable|string|max:20',
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
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

            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|url',
            'status' => ['nullable', Rule::in(TeacherStatus::values())],

            'availability' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'specialization.required' => 'La spécialisation est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé par un autre compte.',
            'regime.in' => 'Le régime sélectionné est invalide.',
            'status.in' => 'Le statut sélectionné est invalide.',
            'website.url' => 'L\'URL du site web n\'est pas valide.',
        ];
    }
}
