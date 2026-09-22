<?php

namespace App\Http\Requests\Support;

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
        return [
            'categorie' => ['required', Rule::in(array_keys(config('support.categories')))],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            // Generee par le navigateur a l'ouverture du brouillon : un double
            // clic ou un renvoi apres coupure retrouve la meme demande.
            'cle' => ['required', 'string', 'regex:/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'],
            'contexte' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'Décrivez ce qui s\'est passé en quelques mots (10 caractères au moins).',
            'description.required' => 'Décrivez ce qui s\'est passé.',
            'categorie.required' => 'Choisissez ce qui correspond le mieux.',
        ];
    }
}
