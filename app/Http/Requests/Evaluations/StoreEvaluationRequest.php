<?php

namespace App\Http\Requests\Evaluations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation pour la création d'une évaluation.
 *
 * Garde-fous critiques : bareme >= 0.1 (pas de division par zéro),
 * coefficient strictement borné [0.1, 10].
 */
class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->can('evaluations.create') || $user->can('evaluations.edit');
    }

    public function rules(): array
    {
        // On accepte un superset des types historiques pour ne pas casser les
        // formulaires existants ; la liste reste fermée (pas d'arbitraire).
        $allowedTypes = ['examen', 'devoir', 'tp', 'projet', 'oral', 'controle', 'quiz', 'cc'];

        // Les périodes acceptées historiquement sont à la fois numériques et
        // sous forme `semestreN`. On garde les deux formats pour rétrocompat.
        $allowedPeriodes = [
            '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
            'semestre1', 'semestre2', 'semestre3', 'semestre4', 'semestre5',
            'semestre6', 'semestre7', 'semestre8', 'semestre9', 'semestre10',
            'Semestre 1', 'Semestre 2', 'Semestre 3', 'Semestre 4', 'Semestre 5',
            'Semestre 6', 'Semestre 7', 'Semestre 8', 'Semestre 9', 'Semestre 10',
        ];

        return [
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'classe_id' => ['required', 'integer', 'exists:esbtp_classes,id'],
            'matiere_id' => ['required', 'integer', 'exists:esbtp_matieres,id'],
            'type' => ['required', 'string', 'in:'.implode(',', $allowedTypes)],
            'date_evaluation' => ['required', 'date'],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
            // Garde-fous critiques : bareme strictement > 0 (sinon division par
            // zéro dans getNoteVingtAttribute), borné à 100.
            'bareme' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'coefficient' => ['required', 'numeric', 'min:0.1', 'max:10'],
            'duree_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'is_published' => ['nullable', 'boolean'],
            'periode' => ['required', 'in:'.implode(',', $allowedPeriodes)],
        ];
    }

    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre est obligatoire.',
            'type.required' => "Le type d'évaluation est obligatoire.",
            'type.in' => "Le type d'évaluation est invalide.",
            'date_evaluation.required' => 'La date est obligatoire.',
            'date_evaluation.date' => 'Le format de la date est invalide.',
            'heure_debut.required' => "L'heure de début est obligatoire.",
            'heure_fin.required' => "L'heure de fin est obligatoire.",
            'heure_fin.after' => "L'heure de fin doit être postérieure à l'heure de début.",
            'classe_id.required' => 'La classe est obligatoire.',
            'classe_id.exists' => "La classe sélectionnée n'existe pas.",
            'matiere_id.required' => 'La matière est obligatoire.',
            'matiere_id.exists' => "La matière sélectionnée n'existe pas.",
            'bareme.required' => 'Le barème est obligatoire.',
            'bareme.min' => 'Le barème doit être strictement supérieur à zéro.',
            'bareme.max' => 'Le barème ne peut pas dépasser 100.',
            'coefficient.required' => "Le coefficient de l'évaluation est obligatoire.",
            'coefficient.numeric' => 'Le coefficient doit être un nombre.',
            'coefficient.min' => 'Le coefficient doit être strictement supérieur à zéro.',
            'coefficient.max' => 'Le coefficient ne peut pas dépasser 10.',
            'duree_minutes.min' => 'La durée doit être supérieure à zéro.',
            'duree_minutes.max' => 'La durée ne peut pas dépasser 480 minutes (8h).',
        ];
    }
}
