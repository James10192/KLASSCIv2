<?php

namespace App\Http\Requests\Reconciliation;

use App\Enums\ModePaiement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordCashCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('comptabilite.reconciliation.open') ?? false;
    }

    public function rules(): array
    {
        return [
            'mode_paiement' => ['required', Rule::in(ModePaiement::values())],
            'montant_compte' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
