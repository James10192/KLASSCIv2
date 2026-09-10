<?php

namespace App\Http\Requests\Matiere;

use App\Domain\BtsTroncCommun\BulletinSubjectOrder;
use App\Models\ESBTPMatiereFilierNiveau;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Enregistrement de la maquette d'un combo (filiere x niveau) : classification
 * tronc commun / specialite, place sur le bulletin, semestre.
 *
 * Les trois champs sont independants et facultatifs. Un champ ABSENT signifie
 * « ne touche pas a cette valeur » ; un champ present a `null` signifie
 * « efface cette valeur ». La distinction compte : enregistrer un ordre ne doit
 * pas effacer un semestre, et surtout ne doit pas activer une maquette que
 * personne n'a validee.
 */
class ClassificationSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le groupe de routes porte deja `permission:matieres.edit`.
        return true;
    }

    public function rules(): array
    {
        return [
            'filiere_id' => ['required', 'integer', 'exists:esbtp_filieres,id'],
            'niveau_id' => ['required', 'integer', 'exists:esbtp_niveau_etudes,id'],

            'classifications' => ['required', 'array', 'min:1'],
            'classifications.*.matiere_id' => ['required', 'integer', 'exists:esbtp_matieres,id'],
            'classifications.*.classification' => [
                'nullable',
                Rule::in([ESBTPMatiereFilierNiveau::TRONC_COMMUN, ESBTPMatiereFilierNiveau::SPECIALITE]),
            ],
            // Borne haute alignee sur le stockage (unsignedSmallInteger) : un rang
            // hors bornes serait tronque en silence par MySQL.
            'classifications.*.ordre_bulletin' => ['nullable', 'integer', 'min:1', 'max:'.BulletinSubjectOrder::RANG_MAX],
            'classifications.*.semestre' => ['nullable', 'integer', 'in:1,2'],

            // Vaut « l'utilisateur a valide les semestres de ce combo ». Sans ce
            // drapeau, une maquette entierement reglee sur « les deux semestres »
            // serait indiscernable d'une maquette jamais remplie.
            'valider_semestres' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'classifications.*.semestre.in' => 'Le semestre doit valoir 1 ou 2, ou rester vide pour « les deux semestres ».',
            'classifications.*.ordre_bulletin.min' => 'La place sur le bulletin commence a 1.',
            'classifications.*.ordre_bulletin.max' => 'La place sur le bulletin ne peut pas depasser :max.',
        ];
    }
}
