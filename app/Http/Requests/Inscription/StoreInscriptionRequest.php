<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Foundation\Http\FormRequest;

class StoreInscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'nom' => 'required|string|max:100',
            'prenoms' => 'required|string|max:100',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'nullable|string|max:100',
            'telephone' => 'required|string|max:20',
            'email_personnel' => 'nullable|email|max:100',
            'ville' => 'nullable|string|max:100',
            'commune' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        // Matricule dynamique : requis seulement si fourni (non vide)
        $matricule = trim((string) $this->input('matricule'));
        $rules['matricule'] = empty($matricule)
            ? 'nullable'
            : 'required|string|max:20|unique:esbtp_etudiants,matricule';

        // Règles dynamiques pour les parents
        $parents = $this->input('parents', []);
        foreach ($parents as $index => $parent) {
            if ($index === 'template') {
                continue;
            }
            if (isset($parent['type']) && $parent['type'] === 'nouveau') {
                $hasData = !empty($parent['nom']) || !empty($parent['prenoms'])
                    || !empty($parent['telephone']) || !empty($parent['email'])
                    || !empty($parent['profession']) || !empty($parent['adresse']);

                if ($hasData) {
                    $rules["parents.$index.nom"] = 'required|string|max:100';
                    $rules["parents.$index.prenoms"] = 'required|string|max:100';
                    $rules["parents.$index.telephone"] = 'required|string|max:20';
                    $rules["parents.$index.relation"] = 'required|string';
                }
            } elseif (isset($parent['type']) && $parent['type'] === 'existant') {
                $rules["parents.$index.parent_id"] = 'required|exists:esbtp_parents,id';
                $rules["parents.$index.relation"] = 'required|string';
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'classe_id.required' => 'Veuillez sélectionner une classe',
            'nom.required' => 'Le nom est obligatoire',
            'prenoms.required' => 'Le(s) prénom(s) est/sont obligatoire(s)',
            'sexe.required' => 'Le genre est obligatoire',
            'date_naissance.required' => 'La date de naissance est obligatoire',
            'telephone.required' => 'Le numéro de téléphone est obligatoire',
            'matricule.required' => 'Le matricule est obligatoire',
            'matricule.unique' => 'Ce matricule existe déjà',
            'photo.image' => 'Le fichier photo doit être une image valide.',
            'photo.mimes' => 'La photo doit être au format JPEG, PNG, JPG ou GIF.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'photo.uploaded' => 'La photo n\'a pas pu être téléchargée. Vérifiez la taille du fichier.',
        ];

        // Messages dynamiques pour les parents
        $parents = $this->input('parents', []);
        foreach ($parents as $index => $parent) {
            if ($index === 'template') {
                continue;
            }
            $messages["parents.$index.nom.required"] = 'Le nom du parent/tuteur est obligatoire';
            $messages["parents.$index.prenoms.required"] = 'Le(s) prénom(s) du parent/tuteur est/sont obligatoire(s)';
            $messages["parents.$index.telephone.required"] = 'Le téléphone du parent/tuteur est obligatoire';
            $messages["parents.$index.relation.required"] = 'La relation avec le parent/tuteur est obligatoire';
        }

        return $messages;
    }

    /**
     * Préparer les données — nettoyer les parents (supprimer template, normaliser existant).
     */
    protected function prepareForValidation(): void
    {
        $parents = $this->input('parents', []);
        $cleaned = [];

        foreach ($parents as $index => $parent) {
            if ($index === 'template') {
                continue;
            }
            if (isset($parent['type']) && $parent['type'] === 'existant') {
                $cleaned[$index] = [
                    'type' => 'existant',
                    'parent_id' => $parent['parent_id'] ?? null,
                    'relation' => $parent['relation'] ?? null,
                ];
            } else {
                $cleaned[$index] = $parent;
            }
        }

        $this->merge(['parents' => $cleaned]);
    }
}
