<?php

namespace App\Http\Requests\Bulletin;

use Illuminate\Foundation\Http\FormRequest;

class GenerateClasseBulletinsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classe_id'              => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'periode'                => 'required|in:semestre1,semestre2,annuel',
            'recalculer'             => 'sometimes|boolean',
            'incomplete_reason'      => 'nullable|string|min:8|max:1000',
        ];
    }
}
