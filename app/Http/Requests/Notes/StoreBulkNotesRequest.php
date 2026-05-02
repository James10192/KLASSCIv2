<?php

namespace App\Http\Requests\Notes;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validation pour la sauvegarde en masse des notes (AJAX).
 *
 * Couvre saveNotesAjaxBulk (POST /esbtp/notes/save-ajax-bulk).
 */
class StoreBulkNotesRequest extends FormRequest
{
    /**
     * Plafond strict pour éviter les abus volumétriques.
     */
    public const MAX_NOTES_PER_BATCH = 500;

    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->can('notes.create') || $user->can('notes.edit') || $user->can('notes.manage_own');
    }

    public function rules(): array
    {
        return [
            'classe_id' => ['nullable', 'integer', 'exists:esbtp_classes,id'],
            'matiere_id' => ['nullable', 'integer', 'exists:esbtp_matieres,id'],
            'notes' => ['required', 'array', 'min:1', 'max:'.self::MAX_NOTES_PER_BATCH],
            'notes.*.evaluation_id' => ['required', 'integer', 'exists:esbtp_evaluations,id'],
            'notes.*.etudiant_id' => ['required', 'integer', 'exists:esbtp_etudiants,id'],
            // Borne haute défense en profondeur — la cohérence note/barème
            // est ensuite vérifiée dans le service via processNoteEntry.
            'notes.*.note' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes.*.is_absent' => ['nullable', 'boolean'],
            'notes.*.commentaire' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'Aucune note à enregistrer.',
            'notes.array' => 'Le format des notes est invalide.',
            'notes.min' => 'Au moins une note doit être fournie.',
            'notes.max' => 'Vous ne pouvez pas enregistrer plus de '.self::MAX_NOTES_PER_BATCH.' notes en une seule requête.',
            'notes.*.evaluation_id.required' => "L'évaluation est obligatoire pour chaque note.",
            'notes.*.evaluation_id.exists' => "Une évaluation référencée n'existe pas.",
            'notes.*.etudiant_id.required' => "L'étudiant est obligatoire pour chaque note.",
            'notes.*.etudiant_id.exists' => "Un étudiant référencé n'existe pas.",
            'notes.*.note.numeric' => 'La note doit être un nombre.',
            'notes.*.note.min' => 'La note ne peut pas être négative.',
            'notes.*.note.max' => 'La note dépasse la borne maximale autorisée (100).',
        ];
    }

    /**
     * Coerce is_absent en booléen sur chaque entrée — l'UI envoie souvent
     * "on" / "1" / "true" pour les checkboxes.
     */
    protected function prepareForValidation(): void
    {
        $notes = $this->input('notes');
        if (! is_array($notes)) {
            return;
        }

        foreach ($notes as $key => $entry) {
            if (isset($entry['is_absent'])) {
                $notes[$key]['is_absent'] = filter_var(
                    $entry['is_absent'],
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                ) ?? false;
            }
        }

        $this->merge(['notes' => $notes]);
    }

    /**
     * Toujours retourner du JSON (les endpoints AJAX consomment cette structure).
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }
}
