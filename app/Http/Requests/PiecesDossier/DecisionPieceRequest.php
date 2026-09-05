<?php

namespace App\Http\Requests\PiecesDossier;

use App\Enums\EtatPieceDossier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valider ou refuser un dépôt, et écarter une pièce.
 *
 * Le motif est exigé ICI, pour que l'utilisateur voie un message. Le modèle et
 * la contrainte en base le réexigent derrière, mais ceux-là rendent une erreur
 * serveur : ce sont des filets pour les imports et les commandes, pas pour un
 * formulaire.
 */
class DecisionPieceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pieces_dossier.suivre') ?? false;
    }

    public function rules(): array
    {
        return [
            'etat' => ['required', Rule::in([
                EtatPieceDossier::VALIDEE->value,
                EtatPieceDossier::REFUSEE->value,
            ])],
            'motif' => [
                Rule::requiredIf(fn () => $this->input('etat') === EtatPieceDossier::REFUSEE->value),
                'nullable',
                'string',
                'min:3',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Un refus doit dire pourquoi : indiquez le motif.',
            'motif.min' => 'Le motif doit être compréhensible par la personne qui lira le dossier après vous.',
        ];
    }

    public function etat(): EtatPieceDossier
    {
        return EtatPieceDossier::from((string) $this->input('etat'));
    }
}
