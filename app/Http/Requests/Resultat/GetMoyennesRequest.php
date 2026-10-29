<?php

namespace App\Http\Requests\Resultat;

use Illuminate\Foundation\Http\FormRequest;

class GetMoyennesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'matiere_id' => 'nullable|exists:esbtp_matieres,id',
            'matiere_ids' => 'nullable|array',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|in:1,2',
            'etudiant_ids' => 'required|array',
        ];
    }
}
