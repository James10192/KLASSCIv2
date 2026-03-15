<?php

namespace App\Http\Requests\Resultat;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateAbsencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'semestre' => 'required|in:1,2',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'absences' => 'required|array',
            'absences.*.etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'absences.*.absences_justifiees' => 'nullable|numeric|min:0',
            'absences.*.absences_non_justifiees' => 'nullable|numeric|min:0',
        ];
    }
}
