<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Foundation\Http\FormRequest;

class TransferOverpaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_category_id' => 'required|exists:esbtp_frais_categories,id',
            'amount' => 'required|numeric|min:0',
            'destinations' => 'required|array|min:1',
            'destinations.*.category_id' => 'required|exists:esbtp_frais_categories,id',
            'destinations.*.amount' => 'required|numeric|min:1',
            'comment' => 'nullable|string|max:500',
        ];
    }
}
