<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation pour l'assignation/désassignation du responsable d'une UE
 * (Directive UEMOA 03/2007/CM — 1 responsable par UE).
 *
 * Payload :
 *   - responsable_ue_id : int|null (null pour désassigner)
 *
 * Le controller (`updateUeResponsable`) vérifie en plus que l'utilisateur
 * cible porte le rôle `enseignant` — pas exprimable proprement en règle
 * Laravel sans custom rule, et la vérif `exists:users,id` ci-dessous
 * couvre déjà l'IDOR de base.
 */
class UpdateUeResponsableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('lmd.planning.edit');
    }

    public function rules(): array
    {
        return [
            'responsable_ue_id' => 'nullable|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'responsable_ue_id.integer' => 'Le responsable doit être un identifiant valide.',
            'responsable_ue_id.exists'  => 'Le responsable sélectionné est introuvable.',
        ];
    }
}
