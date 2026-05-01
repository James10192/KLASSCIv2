<?php

namespace App\Http\Requests\Enseignant;

use App\Enums\TeacherRegime;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class QuickStoreEnseignantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'titre_academique' => 'nullable|string|max:10',
            'grade_academique' => 'nullable|string|max:50',
            'specialization' => 'required|string|max:255',
            'regime' => ['nullable', Rule::in(TeacherRegime::values())],
            // @deprecated 2026-Q3 — type_contrat/statut_emploi/date_embauche : retrocompat AJAX clients
            // d'avant PR #287. Les modals quick-create modernes envoient regime + date_debut_activite.
            // À retirer après audit confirmant zéro client legacy actif.
            'type_contrat' => 'nullable|in:permanent,temporaire,vacataire,consultant',
            'statut_emploi' => 'nullable|in:temps_plein,temps_partiel,vacations',
            'date_debut_activite' => 'nullable|date',
            'date_embauche' => 'nullable|date',
            'taux_horaire' => 'nullable|numeric|min:0',
            'charge_horaire_max_semaine' => 'nullable|integer|min:1|max:60',
            'planification_id' => 'nullable|exists:esbtp_planifications_academiques,id',
            'availability' => 'nullable|array',
        ];
    }

    /**
     * QuickStore est appelé via AJAX — retourner une erreur JSON 422 plutôt qu'une redirection.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422));
    }
}
