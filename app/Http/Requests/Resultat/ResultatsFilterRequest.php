<?php

namespace App\Http\Requests\Resultat;

use Illuminate\Foundation\Http\FormRequest;

class ResultatsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classe_id' => 'nullable|exists:esbtp_classes,id',
            'semestre' => 'nullable|in:1,2',
            'periode' => 'nullable|in:1,2,semestre1,semestre2',
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'include_all_statuses' => 'nullable|boolean',
        ];
    }
}
