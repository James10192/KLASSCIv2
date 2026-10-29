<?php

namespace App\Http\Requests\Resultat;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateProfesseursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'periode' => 'required|in:semestre1,semestre2',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'professeurs' => 'required|array',
            'professeurs.*.matiere_id' => 'required|exists:esbtp_matieres,id',
            'professeurs.*.enseignant_id' => 'nullable|exists:esbtp_teachers,id',
        ];
    }
}
