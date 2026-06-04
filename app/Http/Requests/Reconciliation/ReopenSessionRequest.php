<?php

namespace App\Http\Requests\Reconciliation;

use Illuminate\Foundation\Http\FormRequest;

class ReopenSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('comptabilite.reconciliation.bypass_lock') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:30', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.min' => 'Le motif de réouverture doit faire au moins 30 caractères (cas exceptionnel, audit fiscal).',
        ];
    }
}
