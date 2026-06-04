<?php

namespace App\Http\Requests\Reconciliation;

use Illuminate\Foundation\Http\FormRequest;

class OpenSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('comptabilite.reconciliation.open') ?? false;
    }

    public function rules(): array
    {
        return [
            'frequency' => ['nullable', 'in:daily,weekly,monthly'],
            'start_date' => ['nullable', 'date'],
        ];
    }
}
