<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Foundation\Http\FormRequest;

class UnsubscribeFromOptionalFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'frais_category_id' => 'required|exists:esbtp_frais_categories,id',
        ];
    }
}
