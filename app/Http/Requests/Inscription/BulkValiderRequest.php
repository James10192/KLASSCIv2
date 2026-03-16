<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Foundation\Http\FormRequest;

class BulkValiderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inscription_ids' => 'required|array',
            'inscription_ids.*' => 'exists:esbtp_inscriptions,id',
        ];
    }
}
