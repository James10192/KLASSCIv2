<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Foundation\Http\FormRequest;

class ChangerClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nouvelle_classe_id' => 'required|exists:esbtp_classes,id',
        ];
    }
}
