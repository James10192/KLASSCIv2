<?php

namespace App\Http\Requests\Bulletin;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateMoyennesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classe_id'                    => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id'        => 'required|exists:esbtp_annee_universitaires,id',
            'semestre'                     => 'required|in:1,2',
            'moyennes'                     => 'required|array',
            'moyennes.*.etudiant_id'       => 'required|exists:esbtp_etudiants,id',
            'moyennes.*.matiere_id'        => 'required|exists:esbtp_matieres,id',
            'moyennes.*.moyenne'           => 'nullable|numeric|min:0|max:20',
        ];
    }
}
