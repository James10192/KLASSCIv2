<?php

namespace App\Http\Requests\Bulletin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBulletinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resultats'                  => 'required|array',
            'resultats.*.matiere_id'     => 'required|exists:esbtp_matieres,id',
            'resultats.*.moyenne'        => 'nullable|numeric|min:0|max:20',
            'resultats.*.coefficient'    => 'required|numeric|min:0',
            'resultats.*.commentaire'    => 'nullable|string',
            'appreciation_generale'      => 'nullable|string',
            'decision_conseil'           => 'nullable|string',
        ];
    }
}
