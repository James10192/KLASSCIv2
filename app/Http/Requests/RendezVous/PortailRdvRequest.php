<?php

namespace App\Http\Requests\RendezVous;

use Illuminate\Foundation\Http\FormRequest;

class PortailRdvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:20'],
            'date_naissance' => ['required', 'date_format:Y-m-d'],
            'ip_client' => ['required', 'ip'],
            'creneau_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
