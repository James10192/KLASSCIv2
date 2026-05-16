<?php

namespace App\Http\Requests\Tpe;

use App\Models\ESBTPTpeDeclaration;
use Illuminate\Foundation\Http\FormRequest;

class RejectTpeDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->can('tpe.validate')) {
            return false;
        }

        /** @var ESBTPTpeDeclaration|null $declaration */
        $declaration = $this->route('declaration');
        if (! $declaration instanceof ESBTPTpeDeclaration) {
            return false;
        }

        // Ownership : seul l'enseignant principal de l'ECUE peut rejeter
        // (superAdmin couvert par Gate::before — pas besoin de l'expliciter).
        return $declaration->canBeValidatedBy($user);
    }

    public function rules(): array
    {
        return [
            'commentaire_rejet' => 'required|string|min:5|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'commentaire_rejet.required' => 'Un motif de rejet est obligatoire — l\'étudiant doit savoir comment corriger.',
            'commentaire_rejet.min' => 'Le motif doit faire au moins 5 caractères.',
            'commentaire_rejet.max' => 'Le motif est limité à 500 caractères.',
        ];
    }
}
