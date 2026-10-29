<?php

namespace App\Http\Requests\RendezVous;

use Illuminate\Foundation\Http\FormRequest;

class PortailRdvRetrouverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifiant' => ['required', 'string', 'max:40'],
            'date_naissance' => ['required', 'date_format:Y-m-d'],
            'ip_client' => ['required', 'ip'],
        ];
    }
}
