<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentReinscriptionFicheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('students.edit');
    }

    public function rules(): array
    {
        return [
            // ── Étudiant
            'telephone'                     => 'required|string|max:20',
            'email_personnel'               => 'nullable|email|max:255',
            'ville'                         => 'required|string|max:255',
            'commune'                       => 'required|string|max:255',
            'adresse'                       => 'nullable|string',
            'situation_matrimoniale'        => 'nullable|in:celibataire,marie,divorce,veuf,union_libre',
            'nombre_enfants'                => 'nullable|integer|min:0|max:50',
            'groupe_sanguin'                => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'urgence_contact_nom'           => 'nullable|string|max:255',
            'urgence_contact_telephone'     => 'nullable|string|max:20',
            'urgence_contact_relation'      => 'nullable|string|max:50',

            // ── Parents (max 2)
            'parents'                       => 'nullable|array|max:2',
            'parents.*.parent_id'           => 'nullable|exists:esbtp_parents,id',
            'parents.*.nom'                 => 'nullable|string|max:255|required_with:parents.*.prenoms,parents.*.telephone',
            'parents.*.prenoms'             => 'nullable|string|max:255|required_with:parents.*.nom',
            'parents.*.sexe'                => 'nullable|in:M,F',
            'parents.*.profession'          => 'nullable|string|max:255',
            'parents.*.adresse'             => 'nullable|string',
            'parents.*.telephone'           => 'nullable|string|max:20|required_with:parents.*.nom',
            'parents.*.telephone_secondaire'=> 'nullable|string|max:20',
            'parents.*.email'               => 'nullable|email|max:255',
            'parents.*.type_piece_identite' => 'nullable|in:CNI,Passeport,Permis,Carte_consulaire,Autre',
            'parents.*.numero_piece_identite'=> 'nullable|string|max:50',
            'parents.*.relation'            => 'nullable|string|max:50|required_with:parents.*.nom',
            'parents.*.is_tuteur'           => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.required'  => 'Le téléphone de l\'étudiant est obligatoire.',
            'ville.required'      => 'La ville de résidence est obligatoire.',
            'commune.required'    => 'La commune est obligatoire.',
            'parents.max'         => 'Vous ne pouvez attacher au maximum que 2 parents / tuteurs.',
            'parents.*.nom.required_with'      => 'Le nom du parent est obligatoire dès que vous remplissez ses prénoms ou son téléphone.',
            'parents.*.prenoms.required_with'  => 'Les prénoms du parent sont obligatoires dès que vous remplissez son nom.',
            'parents.*.telephone.required_with'=> 'Le téléphone du parent est obligatoire dès que vous remplissez son nom.',
            'parents.*.relation.required_with' => 'La relation avec l\'étudiant est obligatoire dès que vous remplissez le nom du parent.',
            'parents.*.email.email'            => 'L\'email du parent n\'est pas valide.',
        ];
    }
}
