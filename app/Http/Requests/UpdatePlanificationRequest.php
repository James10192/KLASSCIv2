<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation pour l'édition inline d'une planification académique LMD
 * (volumes horaires, crédits, coefficient, enseignant principal).
 *
 * Tous les champs sont optionnels — on n'envoie que ceux qui sont édités
 * dans la cellule cliquée. Le controller recalcule `volume_horaire_total`
 * automatiquement à partir des cinq sous-volumes (CM/TD/TP/Projet/TPE).
 */
class UpdatePlanificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('lmd.planning.edit');
    }

    public function rules(): array
    {
        return [
            'volume_horaire_cm'       => 'sometimes|nullable|integer|min:0|max:500',
            'volume_horaire_td'       => 'sometimes|nullable|integer|min:0|max:500',
            'volume_horaire_tp'       => 'sometimes|nullable|integer|min:0|max:500',
            'volume_horaire_projet'   => 'sometimes|nullable|integer|min:0|max:500',
            'volume_horaire_tpe'      => 'sometimes|nullable|integer|min:0|max:500',
            'coefficient'             => 'sometimes|nullable|numeric|min:0|max:10',
            'credits_ects'            => 'sometimes|nullable|integer|min:0|max:30',
            'enseignant_principal_id' => 'sometimes|nullable|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'volume_horaire_cm.integer'       => 'Le volume CM doit être un nombre entier.',
            'volume_horaire_cm.max'           => 'Le volume CM ne peut excéder 500 heures.',
            'volume_horaire_td.integer'       => 'Le volume TD doit être un nombre entier.',
            'volume_horaire_tp.integer'       => 'Le volume TP doit être un nombre entier.',
            'volume_horaire_projet.integer'   => 'Le volume Projet doit être un nombre entier.',
            'volume_horaire_tpe.integer'      => 'Le volume TPE doit être un nombre entier.',
            'coefficient.numeric'             => 'Le coefficient doit être un nombre.',
            'coefficient.max'                 => 'Le coefficient ne peut excéder 10.',
            'credits_ects.integer'            => 'Les crédits doivent être un nombre entier.',
            'credits_ects.max'                => 'Les crédits ne peuvent excéder 30.',
            'enseignant_principal_id.exists'  => 'L\'enseignant sélectionné est introuvable.',
        ];
    }
}
