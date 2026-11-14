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
            // Jeton rendu par un code reussi (AccesRendezVous) : exige quand
            // l'ecole a active la verification du contact.
            'jeton_acces' => ['nullable', 'string', 'max:600'],
            // Lecture sans envoi de code : l'ecran qui suit un depot affiche le
            // creneau s'il le peut, il ne declenche jamais de message.
            'sans_code' => ['nullable', 'boolean'],
        ];
    }
}
