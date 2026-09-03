<?php

namespace App\Http\Requests\LMD;

use App\Enums\TypeUE;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation du formulaire « Unité d'Enseignement » (création et modification).
 *
 * Pourquoi une seule classe pour les deux : les règles sont identiques à
 * l'unicité du code près, qui doit ignorer l'UE en cours de modification.
 *
 * Cette classe couvre TOUS les champs réellement postés par le formulaire
 * (resources/views/esbtp/lmd/ue/partials/_form.blade.php). Auparavant, seuls
 * name / code / description / credit / type_ue étaient validés : le semestre,
 * la filière, le niveau, le parcours, l'ordre et la liste des ECUE étaient
 * silencieusement jetés, et l'UE créée n'était rattachée à rien — donc absente
 * des calculs, du bulletin et du procès-verbal.
 */
class UniteEnseignementRequest extends FormRequest
{
    /**
     * L'accès est déjà filtré par les middlewares du groupe de routes
     * (« permission:module.lmd.access » + rôles habilités). Refaire un contrôle
     * de permission ici, avec un nom de permission différent, retirerait l'accès
     * à des utilisateurs qui l'ont aujourd'hui.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ue = $this->route('ue');
        $uniqueCode = Rule::unique('esbtp_unites_enseignement', 'code');
        if ($ue) {
            $uniqueCode = $uniqueCode->ignore($ue->id ?? $ue);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', $uniqueCode],
            'description' => ['nullable', 'string'],
            'credit' => ['nullable', 'integer', 'min:0'],
            'type_ue' => ['required', Rule::in(TypeUE::values())],

            // Le semestre porte le rattachement au parcours (colonne NOT NULL du
            // pivot esbtp_lmd_parcours_ue) : sans lui, le lien serait impossible.
            'semestre' => ['nullable', 'integer', 'between:1,10', 'required_with:parcours_id'],
            'parcours_id' => ['nullable', 'integer', 'exists:esbtp_lmd_parcours,id'],
            'filiere_id' => ['nullable', 'integer', 'exists:esbtp_filieres,id'],
            'niveau_id' => ['nullable', 'integer', 'exists:esbtp_niveau_etudes,id'],
            'ordre' => ['nullable', 'integer', 'min:0'],

            'ecues' => ['nullable', 'array'],
            'ecues.*.name' => ['required', 'string', 'max:255'],
            'ecues.*.code' => ['nullable', 'string', 'max:50', 'distinct:ignore_case'],
            'ecues.*.coefficient_ecue' => ['nullable', 'numeric', 'min:0'],
            'ecues.*.credit_ecue' => ['nullable', 'integer', 'min:0'],
            'ecues.*.ordre_bulletin' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'intitulé',
            'code' => 'code',
            'credit' => 'crédits',
            'type_ue' => 'type d\'UE',
            'semestre' => 'semestre',
            'parcours_id' => 'parcours',
            'filiere_id' => 'filière',
            'niveau_id' => 'niveau',
            'ordre' => 'ordre sur le bulletin',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Ce code est déjà utilisé par une autre unité d\'enseignement.',
            'semestre.required_with' => 'Choisissez un semestre : c\'est lui qui rattache l\'unité d\'enseignement au parcours.',
            'ecues.*.name.required' => 'Chaque élément constitutif doit avoir un intitulé.',
            'ecues.*.code.distinct' => 'Deux éléments constitutifs ne peuvent pas porter le même code.',
        ];
    }

    /**
     * Un ECUE ne peut pas apporter plus de crédits que n'en porte son UE.
     * Même règle que l'ajout d'un ECUE isolé (checkCreditOverflow du contrôleur),
     * appliquée ici à la somme de la liste soumise.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $creditUe = $this->input('credit');
            if ($creditUe === null || $creditUe === '') {
                return;
            }

            $sommeEcues = 0;
            foreach ((array) $this->input('ecues', []) as $ecue) {
                $sommeEcues += (int) ($ecue['credit_ecue'] ?? 0);
            }

            if ($sommeEcues > (int) $creditUe) {
                $validator->errors()->add(
                    'credit',
                    "La somme des crédits des éléments constitutifs ({$sommeEcues}) dépasse les crédits de l'unité d'enseignement ({$creditUe})."
                );
            }
        });
    }
}
