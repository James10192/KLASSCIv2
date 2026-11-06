<?php

namespace App\Http\Requests\Comptabilite;

use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SuiviClassesFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('comptabilite.suivi_classes.view');
    }

    public function rules(): array
    {
        return [
            // Un identifiant inconnu est refusé : sinon le rapport sort vide sans dire pourquoi.
            'annee' => ['nullable', 'integer', 'min:1', Rule::exists('esbtp_annee_universitaires', 'id')],
            'filiere' => ['nullable', 'integer', 'min:1', Rule::exists('esbtp_filieres', 'id')],
            'niveau' => ['nullable', 'integer', 'min:1', Rule::exists('esbtp_niveau_etudes', 'id')],
            'classe' => ['nullable', 'integer', 'min:1', Rule::exists('esbtp_classes', 'id')],
            'categorie' => ['nullable', 'integer', 'min:1', Rule::exists('esbtp_frais_categories', 'id')],
            'systeme' => ['nullable', 'string', Rule::in(
                \App\Models\ESBTPClasse::query()->whereNotNull('systeme_academique')
                    ->distinct()->pluck('systeme_academique')->all()
            )],
            'search' => ['nullable', 'string', 'max:100'],
            'tri' => ['nullable', 'string', Rule::in(array_keys(\App\Domain\Comptabilite\SuiviClasses\SituationClassesService::TRIS))],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filters(): array
    {
        $data = $this->validated();
        if (empty($data['annee'])) {
            $data['annee'] = ESBTPAnneeUniversitaire::getCurrent()?->id;
        }
        $data['search'] = trim((string) ($data['search'] ?? ''));
        return $data;
    }
}
