<?php

namespace App\Http\Requests\Reinscription;

/** Depot d'une demande. */
class PortailSubmitRequest extends PortailRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return parent::rules() + [
            // Consentement explicite : obligation de la loi ivoirienne 2013-450
            // relative a la protection des donnees a caractere personnel.
            'consentement' => ['required', 'accepted'],
            // Facultatifs : l'adresse que la famille lit vraiment, et le canal
            // du code de confirmation. Absents, le dossier decide comme avant.
            'email' => ['nullable', 'string', 'email', 'max:254'],
            'canal' => ['nullable', 'in:email,telephone'],
        ];
    }
}
