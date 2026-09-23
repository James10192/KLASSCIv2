<?php

namespace App\Http\Requests\Permissions;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccesTemporaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('permissions.temporaires.manage');
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'permission' => ['required', 'string', 'max:125'],
            'debut' => ['nullable', 'date'],
            'fin' => ['required', 'date', 'after:now'],
            'motif' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Choisissez la personne.',
            'permission.required' => 'Choisissez la permission.',
            'fin.required' => 'Indiquez jusqu\'à quand l\'accès reste ouvert.',
            'fin.after' => 'La fin de l\'accès doit être dans le futur.',
            'motif.required' => 'Le motif est obligatoire : il reste dans l\'historique.',
            'motif.min' => 'Le motif doit faire au moins 10 caractères.',
        ];
    }
}
