<?php

namespace App\Http\Requests\Support;

use App\Services\Care\ClientMasterSupport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SoumettreDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Les limites du Master, pas une copie : s'il les change, ce formulaire suit.
        $l = app(ClientMasterSupport::class)->limites();

        return [
            'categorie' => ['required', Rule::in(array_keys(config('support.categories')))],
            'description' => ['required', 'string', 'min:'.$l['description_min'], 'max:'.$l['description_max']],
            // Generee par le navigateur a l'ouverture du brouillon : un double
            // clic ou un renvoi apres coupure retrouve la meme demande.
            'cle' => ['required', 'string', 'regex:/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'],
            'contexte' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'Décrivez ce qui s\'est passé en quelques mots (:min caractères au moins).',
            'description.required' => 'Décrivez ce qui s\'est passé.',
            'categorie.required' => 'Choisissez ce qui correspond le mieux.',
        ];
    }
}
