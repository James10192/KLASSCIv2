<?php

namespace App\Http\Requests\Notes;

use Illuminate\Foundation\Http\FormRequest;

class ImportNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->can('notes.import_excel') || $user->can('notes.create') || $user->can('notes.edit');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
            'classe_id' => ['required', 'integer', 'exists:esbtp_classes,id'],
            'matiere_id' => ['required', 'integer', 'exists:esbtp_matieres,id'],
            'periode' => ['required', 'in:semestre1,semestre2'],
            'annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annee_universitaires,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Veuillez sélectionner un fichier Excel à importer.',
            'file.mimes' => 'Le fichier doit être au format xlsx, xls ou csv.',
            'file.max' => 'Le fichier ne doit pas dépasser 5 Mo.',
            'classe_id.required' => 'La classe est obligatoire.',
            'matiere_id.required' => 'La matière est obligatoire.',
            'periode.required' => 'La période est obligatoire.',
            'periode.in' => 'La période doit être semestre1 ou semestre2.',
        ];
    }
}
