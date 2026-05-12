<?php

namespace App\Http\Requests;

use App\Enums\TypeSeance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeanceCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\ESBTPSeanceCours::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'matiere_id'              => 'required|exists:esbtp_matieres,id',
            'enseignant_id'           => 'required|exists:users,id',
            'type_seance'             => ['required', Rule::enum(TypeSeance::class)],
            'jour'                    => 'required|string|max:20',
            'heure_debut'             => 'required|date_format:H:i',
            'heure_fin'               => 'required|date_format:H:i|after:heure_debut',
            'salle'                   => 'nullable|string|max:50',
            'description'             => 'nullable|string',
            'classe_id'               => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id'  => 'required|exists:esbtp_annee_universitaires,id',
        ];
    }

    public function messages(): array
    {
        return [
            'type_seance.required' => 'Le type de séance est obligatoire.',
            'type_seance.enum'     => 'Le type de séance sélectionné n\'est pas valide.',
        ];
    }
}
