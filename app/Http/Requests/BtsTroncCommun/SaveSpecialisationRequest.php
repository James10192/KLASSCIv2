<?php

namespace App\Http\Requests\BtsTroncCommun;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSpecialisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inscriptions.specialisation.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'filiere_id' => ['required', 'integer', 'exists:esbtp_filieres,id'],
            'classe_id' => [
                'required',
                'integer',
                Rule::exists('esbtp_classes', 'id')
                    ->where(fn ($query) => $query->where('filiere_id', $this->integer('filiere_id'))),
            ],
            'correction_reason' => [
                $this->isMethod('PATCH') ? 'required' : 'nullable',
                'string',
                'min:10',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'correction_reason.required' => 'Le motif de correction est obligatoire.',
            'correction_reason.min' => 'Le motif de correction doit contenir au moins 10 caractères.',
        ];
    }
}
