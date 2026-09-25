<?php

namespace App\Http\Requests\LMD;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Saisie groupée des notes LMD (écran « saisie rapide »).
 *
 * « 12,5 » est une note : la virgule décimale est remplacée avant la
 * validation numérique, qui la refusait. Le dépassement du barème, lui,
 * dépend de l'évaluation et reste vérifié par le contrôleur, après les
 * gardes d'accès.
 */
class SaveBulkLmdNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['notes' => collect($this->input('notes', []))->map(function ($n) {
            if (is_array($n) && isset($n['note']) && is_string($n['note'])) {
                $valeur = trim(str_replace([',', ' '], ['.', ''], $n['note']));
                $n['note'] = $valeur === '' ? null : $valeur;
            }

            return $n;
        })->all()]);
    }

    public function rules(): array
    {
        return [
            'evaluation_id' => 'required|exists:esbtp_evaluations,id',
            'notes' => 'required|array',
            'notes.*.etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'notes.*.note' => 'nullable|numeric|min:0',
            'notes.*.is_absent' => 'nullable|boolean',
            'notes.*.commentaire' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'notes.*.note.numeric' => 'Une note n’est pas un nombre : saisissez par exemple 12,5.',
            'notes.*.is_absent.boolean' => 'La case « absent » est mal renseignée.',
        ];
    }
}
