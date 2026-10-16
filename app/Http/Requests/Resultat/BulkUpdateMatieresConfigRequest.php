<?php

namespace App\Http\Requests\Resultat;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateMatieresConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|in:1,2',
            'coefficients' => 'nullable|array',
            'coefficients.*' => 'required|numeric|min:0.1',
            'matiere_types' => 'nullable|array',
            'matiere_types.*' => 'required|in:general,technique,none',
        ];
    }
}
