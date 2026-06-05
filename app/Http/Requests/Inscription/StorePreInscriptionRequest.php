<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePreInscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise le matricule manuel (trim + nullify chaîne vide) AVANT validation.
     */
    protected function prepareForValidation(): void
    {
        $matricule = $this->input('matricule');
        if (is_string($matricule)) {
            $matricule = trim($matricule);
            $this->merge(['matricule' => $matricule === '' ? null : $matricule]);
        }
    }

    public function rules(): array
    {
        return [
            'etudiant_existant_id' => 'nullable|integer|exists:esbtp_etudiants,id',
            'nom' => 'required_without:etudiant_existant_id|string|max:100',
            'prenoms' => 'required_without:etudiant_existant_id|string|max:100',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'telephone' => 'nullable|string|max:20',
            // Matricule manuel optionnel pour un nouvel étudiant (ignoré en réinscription)
            'matricule' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9\-_\/]+$/',
                Rule::unique('esbtp_etudiants', 'matricule')->whereNull('deleted_at'),
            ],
            'frais' => 'nullable|array',
            'frais.*.variant_id' => 'nullable|string',
            'frais.*.amount' => 'nullable|numeric|min:0',
            'paiement_categories' => 'nullable|array',
            'paiement_categories.*' => 'integer',
            'paiement_montants' => 'nullable|array',
            'paiement_montants.*' => 'numeric|min:0',
            'mode_paiement' => 'nullable|string|in:especes,cheque,virement,mobile_money',
            'reference_paiement' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required_without' => 'Le nom est obligatoire',
            'prenoms.required_without' => 'Le(s) prénom(s) est/sont obligatoire(s)',
            'classe_id.required' => 'Veuillez sélectionner une classe',
            'matricule.unique' => 'Ce matricule est déjà attribué à un autre étudiant.',
            'matricule.regex' => 'Le matricule ne peut contenir que des lettres, chiffres, tirets, underscores ou /.',
            'matricule.max' => 'Le matricule ne peut excéder 50 caractères.',
        ];
    }
}
