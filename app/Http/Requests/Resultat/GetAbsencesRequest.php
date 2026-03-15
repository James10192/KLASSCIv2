<?php

namespace App\Http\Requests\Resultat;

use Illuminate\Foundation\Http\FormRequest;

class GetAbsencesRequest extends FormRequest
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
            'etudiant_ids' => 'required|array',
        ];
    }
}
