<?php

namespace App\Http\Requests\Verification;

use Illuminate\Foundation\Http\FormRequest;

/**
 * « Confirmer le contact » : l'empreinte de ce que l'agent avait a l'ecran
 * (sha256, 64 caracteres). L'autorisation est portee par les permissions
 * `*.process` posees sur le controleur.
 */
class ConfirmerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['empreinte' => ['required', 'string', 'size:64']];
    }

    public function empreinte(): string
    {
        return (string) $this->validated('empreinte');
    }
}
