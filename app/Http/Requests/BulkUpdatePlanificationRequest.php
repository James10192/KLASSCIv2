<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation pour l'edition en masse de planifications academiques LMD.
 *
 * Payload attendu :
 *   {
 *     "ecue_ids": [int, int, ...],   // 1..50 ECUE IDs cibles
 *     "fields": {                    // au moins un champ a appliquer
 *       "volume_horaire_cm": 30,
 *       "volume_horaire_td": null,   // null = clear
 *       ...
 *     }
 *   }
 *
 * Le controller applique chaque champ present dans `fields` a TOUTES les ECUE
 * listees dans `ecue_ids`. Les champs absents de `fields` sont laisses
 * intacts. Le total horaire est recalcule par le controller apres chaque
 * upsert (somme des cinq sous-volumes).
 */
class BulkUpdatePlanificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('lmd.planning.edit');
    }

    public function rules(): array
    {
        return [
            'ecue_ids'                       => ['required', 'array', 'min:1', 'max:50'],
            'ecue_ids.*'                     => ['integer', 'exists:esbtp_matieres,id'],
            'fields'                         => ['required', 'array', 'min:1'],
            'fields.volume_horaire_cm'       => ['nullable', 'integer', 'min:0', 'max:500'],
            'fields.volume_horaire_td'       => ['nullable', 'integer', 'min:0', 'max:500'],
            'fields.volume_horaire_tp'       => ['nullable', 'integer', 'min:0', 'max:500'],
            'fields.volume_horaire_projet'   => ['nullable', 'integer', 'min:0', 'max:500'],
            'fields.volume_horaire_tpe'      => ['nullable', 'integer', 'min:0', 'max:500'],
            'fields.coefficient'             => ['nullable', 'numeric', 'min:0', 'max:10'],
            'fields.credits_ects'            => ['nullable', 'integer', 'min:0', 'max:30'],
            'fields.enseignant_principal_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ecue_ids.required'                  => 'Aucun ECUE selectionne pour l\'operation en masse.',
            'ecue_ids.max'                       => 'Maximum 50 ECUE par operation en masse.',
            'ecue_ids.*.exists'                  => 'Un des ECUE selectionnes est introuvable.',
            'fields.required'                    => 'Aucun champ a appliquer en masse.',
            'fields.volume_horaire_cm.integer'   => 'Le volume CM doit etre un nombre entier.',
            'fields.volume_horaire_cm.max'       => 'Le volume CM ne peut exceder 500 heures.',
            'fields.volume_horaire_td.integer'   => 'Le volume TD doit etre un nombre entier.',
            'fields.volume_horaire_tp.integer'   => 'Le volume TP doit etre un nombre entier.',
            'fields.coefficient.numeric'         => 'Le coefficient doit etre un nombre.',
            'fields.coefficient.max'             => 'Le coefficient ne peut exceder 10.',
            'fields.credits_ects.max'            => 'Les credits ne peuvent exceder 30.',
            'fields.enseignant_principal_id.exists' => 'L\'enseignant selectionne est introuvable.',
        ];
    }
}
