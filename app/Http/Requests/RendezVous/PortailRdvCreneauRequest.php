<?php

namespace App\Http\Requests\RendezVous;

class PortailRdvCreneauRequest extends PortailRdvRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'creneau_id' => ['required', 'integer', 'min:1'],
        ]);
    }
}
