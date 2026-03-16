<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Foundation\Http\FormRequest;

class AnnulerInscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif' => 'required|string|max:255',
        ];
    }
}
