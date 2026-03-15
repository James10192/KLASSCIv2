<?php

namespace App\Http\Requests\Resultat;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateMatieresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'matieres' => 'required|array',
            'matieres.*.matiere_id' => 'required|exists:esbtp_matieres,id',
            'matieres.*.coefficient' => 'required|numeric|min:0',
        ];
    }
}
