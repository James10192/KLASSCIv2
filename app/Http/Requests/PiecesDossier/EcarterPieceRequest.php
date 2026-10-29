<?php

namespace App\Http\Requests\PiecesDossier;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Écarter une pièce qui ne concerne pas cet étudiant cette année.
 *
 * Le motif est obligatoire, et ce n'est pas une formalité : c'est ce qui
 * distingue une dispense décidée d'un dossier qu'on a renoncé à réclamer. Six
 * mois plus tard, seul le motif dira lequel des deux.
 */
class EcarterPieceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pieces_dossier.suivre') ?? false;
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Dites pourquoi cette pièce ne concerne pas cet étudiant cette année.',
        ];
    }
}
