<?php

namespace App\Http\Requests\Paiement;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation du motif de rejet d'un paiement (action unitaire ou bulk).
 *
 * Le motif est obligatoire et doit être suffisamment explicite (min 10 chars)
 * pour permettre au caissier qui a saisi le paiement de comprendre la raison
 * du rejet et corriger sa saisie.
 */
class RejeterPaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('paiements.validate') ?? false;
    }

    public function rules(): array
    {
        return [
            'motif_rejet' => 'required|string|min:10|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'motif_rejet.required' => 'Le motif de rejet est obligatoire.',
            'motif_rejet.min' => 'Le motif de rejet doit faire au moins 10 caractères pour être utile au caissier qui a saisi le paiement.',
            'motif_rejet.max' => 'Le motif de rejet ne peut pas dépasser 500 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'motif_rejet' => 'motif de rejet',
        ];
    }
}
