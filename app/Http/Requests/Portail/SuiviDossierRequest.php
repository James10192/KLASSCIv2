<?php

namespace App\Http\Requests\Portail;

use Illuminate\Foundation\Http\FormRequest;

/** Identification d'un dossier deja depose : reference (ou matricule) et date de naissance. */
class SuiviDossierRequest extends FormRequest
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
