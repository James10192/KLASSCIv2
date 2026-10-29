<?php

namespace App\Http\Requests\Notes;

use App\Rules\NoteRespectsBareme;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validation pour la sauvegarde d'une note unitaire (AJAX).
 *
 * Couvre saveNoteAjax (POST /esbtp/notes/save-ajax).
 */
class StoreNoteRequest extends FormRequest
{
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
            'evaluation_id' => ['required', 'integer', 'exists:esbtp_evaluations,id'],
            'etudiant_id' => ['required', 'integer', 'exists:esbtp_etudiants,id'],
            'note' => [
                'nullable',
                'numeric',
                'min:0',
                new NoteRespectsBareme($this->input('evaluation_id')),
            ],
            'is_absent' => ['nullable', 'boolean'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'evaluation_id.required' => "L'évaluation est obligatoire.",
            'evaluation_id.exists' => "L'évaluation sélectionnée n'existe pas.",
            'etudiant_id.required' => "L'étudiant est obligatoire.",
            'etudiant_id.exists' => "L'étudiant sélectionné n'existe pas.",
            'note.numeric' => 'La note doit être un nombre.',
            'note.min' => 'La note ne peut pas être négative.',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser 500 caractères.',
        ];
    }

    /**
     * Coerce is_absent en booléen — l'UI peut envoyer "on", "1", "true".
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_absent')) {
            $raw = $this->input('is_absent');
            $this->merge([
                'is_absent' => filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }
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
