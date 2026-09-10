<?php

declare(strict_types=1);

namespace App\Http\Requests\Dispense;

use Illuminate\Foundation\Http\FormRequest;

class RevoquerDispenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dispenses.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:10', 'max:160'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de la révocation est obligatoire.',
            'motif.min' => 'Le motif doit être explicite : au moins 10 caractères.',
        ];
    }
}
