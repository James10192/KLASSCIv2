<?php

namespace App\Http\Requests\Bulletin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulletinRequest extends FormRequest
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
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'periode'                => 'required|in:semestre1,semestre2,annuel',
            'appreciation_generale'  => 'nullable|string',
            'decision_conseil'       => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'etudiant_id.required'            => "L'étudiant est obligatoire",
            'classe_id.required'              => 'La classe est obligatoire',
            'annee_universitaire_id.required' => "L'année universitaire est obligatoire",
            'periode.required'                => 'La période est obligatoire',
        ];
    }
}
