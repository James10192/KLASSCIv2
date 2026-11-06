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
            'annee' => ['nullable', 'integer', 'min:1'],
            'filiere' => ['nullable', 'integer', 'min:1'],
            'niveau' => ['nullable', 'integer', 'min:1'],
            'classe' => ['nullable', 'integer', 'min:1'],
            'categorie' => ['nullable', 'integer', 'min:1'],
            'systeme' => ['nullable', 'string', Rule::in(
                \App\Models\ESBTPClasse::query()->whereNotNull('systeme_academique')
                    ->distinct()->pluck('systeme_academique')->all()
            )],
            'search' => ['nullable', 'string', 'max:100'],
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
