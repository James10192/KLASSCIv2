<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validation pour l'édition inline d'une planification académique LMD
 * (volumes horaires, crédits, coefficient, pool d'enseignants).
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
            'enseignants_secondaires' => 'sometimes|nullable|array|max:20',
            'enseignants_secondaires.*' => 'integer|distinct|exists:users,id',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ids = collect([$this->input('enseignant_principal_id')])
                ->merge($this->input('enseignants_secondaires', []))
                ->filter()->map(fn ($id) => (int) $id)->unique()->values();

            if ($ids->isEmpty()) {
                return;
            }

            $users = User::whereIn('id', $ids)->get();
            foreach ($users as $user) {
                if (! $user->hasRole('enseignant')) {
                    $validator->errors()->add('enseignants_secondaires', 'Le pool ne peut contenir que des utilisateurs ayant le rôle enseignant.');
                    break;
                }
            }

            if ($ids->count() !== $users->count()) {
                $validator->errors()->add('enseignants_secondaires', 'Un des enseignants sélectionnés est introuvable.');
            }
        });
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
            'enseignant_principal_id.exists'  => 'L\'enseignant principal sélectionné est introuvable.',
            'enseignants_secondaires.array'   => 'La liste des enseignants secondaires est invalide.',
            'enseignants_secondaires.*.exists' => 'Un enseignant secondaire sélectionné est introuvable.',
        ];
    }
}
