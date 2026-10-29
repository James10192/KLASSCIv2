<?php

namespace App\Http\Requests\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\GradeSheetDocumentService;
use Illuminate\Foundation\Http\FormRequest;

class UploadGradeSheetDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expected_lock_version' => ['required', 'integer', 'min:1'],
            'document' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,xls,xlsx',
                'mimetypes:application/pdf,image/jpeg,image/png,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'max:'.GradeSheetDocumentService::MAX_DOCUMENT_SIZE_KB,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'Veuillez sélectionner un document.',
            'document.file' => 'Le document transmis est invalide.',
            'document.mimes' => 'Le document doit être au format PDF, JPG, JPEG, PNG, XLS ou XLSX.',
            'document.mimetypes' => 'Le contenu du document ne correspond pas à un format autorisé.',
            'document.max' => 'Le document ne peut pas dépasser 10 Mo.',
        ];
    }
}
