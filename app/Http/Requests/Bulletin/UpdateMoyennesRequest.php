<?php

namespace App\Http\Requests\Bulletin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMoyennesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'etudiant_id'            => 'required|exists:esbtp_etudiants,id',
            'classe_id'              => 'required|exists:esbtp_classes,id',
            'periode'                => 'required|in:semestre1,semestre2,annuel,1,2',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',

            // Résultats existants (optionnel si nouvelles_matieres présent)
            'resultats'                              => 'array',
            'resultats.*.matiere_id'                 => 'required|exists:esbtp_matieres,id',
            'resultats.*.moyenne'                    => 'required|numeric|min:0|max:20',
            'resultats.*.coefficient'                => 'nullable|numeric|min:0',
            'resultats.*.appreciation'               => 'nullable|string|max:255',

            // Nouvelles matières (optionnel si resultats présent)
            'nouvelles_matieres'                                          => 'array',
            'nouvelles_matieres.*.matiere_type'                           => 'required|string|in:existante,nouvelle',
            'nouvelles_matieres.*.matiere_existante_id'                   => 'required_if:nouvelles_matieres.*.matiere_type,existante|nullable|exists:esbtp_matieres,id',
            'nouvelles_matieres.*.nom_nouvelle'                           => 'required_if:nouvelles_matieres.*.matiere_type,nouvelle|nullable|string|max:255',
            'nouvelles_matieres.*.moyenne'                                => 'required|numeric|min:0|max:20',
            'nouvelles_matieres.*.coefficient'                            => 'required_if:nouvelles_matieres.*.matiere_type,nouvelle|numeric|min:0',
            'nouvelles_matieres.*.appreciation'                           => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->has('resultats') && ! $this->has('nouvelles_matieres')) {
                $validator->errors()->add('resultats', 'Aucune donnée à traiter. Veuillez modifier au moins une moyenne ou ajouter une nouvelle matière.');
            }
        });
    }
}
