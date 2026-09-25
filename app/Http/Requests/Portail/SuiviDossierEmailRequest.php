<?php

namespace App\Http\Requests\Portail;

use App\Rules\EmailJoignable;

/** La famille donne ou corrige l'adresse e-mail de son dossier. */
class SuiviDossierEmailRequest extends SuiviDossierRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'email' => ['required', 'string', 'email:rfc', 'max:150', new EmailJoignable()],
        ];
    }
}
