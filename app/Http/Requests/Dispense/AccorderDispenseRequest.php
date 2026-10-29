<?php

declare(strict_types=1);

namespace App\Http\Requests\Dispense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccorderDispenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dispenses.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'matiere_id' => ['required', 'integer', Rule::exists('esbtp_matieres', 'id')],
            'annee_universitaire_id' => ['required', 'integer', Rule::exists('esbtp_annee_universitaires', 'id')],
            // Vide = toute l'annee. La liste est courte et fermee : une valeur
            // hors de cette liste serait une portee que le bulletin ne sait pas
            // representer.
            'periode' => ['nullable', Rule::in(['semestre1', 'semestre2', 'annuel'])],
            // Un motif obligatoire, et assez long pour vouloir dire quelque
            // chose : « ok » ne se defend pas devant une famille six mois plus tard.
            'motif' => ['required', 'string', 'min:10', 'max:160'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de la dispense est obligatoire.',
            'motif.min' => 'Le motif doit être explicite : au moins 10 caractères.',
            'motif.max' => 'Le motif ne doit pas dépasser 160 caractères.',
        ];
    }
}
