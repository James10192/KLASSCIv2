<?php

namespace App\Http\Requests\Permissions;

use App\Domain\Permissions\AccesTemporaires;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccesTemporaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('permissions.temporaires.manage');
    }

    public function rules(): array
    {
        $definitif = $this->input('mode', AccesTemporaires::MODE_TEMPORAIRE) === AccesTemporaires::MODE_DEFINITIF;

        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'permission' => ['required', 'string', 'max:125'],
            'mode' => ['sometimes', Rule::in(AccesTemporaires::MODES)],
            'debut' => $definitif ? ['prohibited'] : ['nullable', 'date'],
            'fin' => $definitif ? ['prohibited'] : ['required', 'date', 'after:now'],
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
            'fin.prohibited' => 'Un accès définitif ne doit avoir aucune date de fin.',
            'debut.prohibited' => 'Un accès définitif commence immédiatement.',
            'mode.in' => 'Choisissez un mode d’accès valide.',
            'motif.required' => 'Le motif est obligatoire : il reste dans l\'historique.',
            'motif.min' => 'Le motif doit faire au moins 10 caractères.',
        ];
    }
}
