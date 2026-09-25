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
            // `scope=filtre` : la selection est tout le filtre de la liste,
            // recalcule par SelectionDInscriptions, sans liste d'identifiants.
            'inscription_ids' => 'required_unless:scope,filtre|array',
            'inscription_ids.*' => 'exists:esbtp_inscriptions,id',
        ];
    }
}
