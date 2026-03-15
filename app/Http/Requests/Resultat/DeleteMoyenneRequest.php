<?php

namespace App\Http\Requests\Resultat;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMoyenneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'periode' => 'required',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
        ];
    }
}
