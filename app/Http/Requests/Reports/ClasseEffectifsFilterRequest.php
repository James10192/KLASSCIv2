<?php

namespace App\Http\Requests\Reports;

use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClasseEffectifsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('reports.view');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_merge([
            'annee_universitaire_id' => ESBTPAnneeUniversitaire::anneeCourante()?->id,
            'min_inscrits' => 15,
            'nombre_classes' => '20',
            'sort' => 'effectif_desc',
            'page' => 1,
        ], $this->all()));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'annee_universitaire_id' => ['nullable', 'integer', Rule::exists('esbtp_annee_universitaires', 'id')->whereNull('deleted_at')],
            'min_inscrits' => ['required', 'integer', 'between:0,10000'],
            'nombre_classes' => ['required', Rule::in(['10', '20', '50', '100', '200', '500', 'all'])],
            'sort' => ['required', Rule::in(['effectif_desc', 'nom_asc'])],
            'filiere_id' => ['nullable', 'integer', Rule::exists('esbtp_filieres', 'id')->whereNull('deleted_at')],
            'niveau_id' => ['nullable', 'integer', Rule::exists('esbtp_niveau_etudes', 'id')->whereNull('deleted_at')],
            'page' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        $validated = $this->validated();
        foreach (['annee_universitaire_id', 'filiere_id', 'niveau_id'] as $field) {
            $validated[$field] = ($validated[$field] ?? null) === null || ($validated[$field] ?? '') === ''
                ? null : (int) $validated[$field];
        }
        $validated['min_inscrits'] = (int) $validated['min_inscrits'];
        $validated['page'] = (int) $validated['page'];

        return $validated;
    }
}
