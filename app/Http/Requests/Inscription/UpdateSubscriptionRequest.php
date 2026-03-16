<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ];
    }
}
