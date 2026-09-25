<?php

namespace App\Http\Requests\Portail;

use Illuminate\Foundation\Http\FormRequest;

class ReferenceOublieeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:150'],
            'date_naissance' => ['required', 'date_format:Y-m-d'],
            'ip_client' => ['required', 'ip'],
        ];
    }
}
