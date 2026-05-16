<?php

namespace App\Http\Requests\Attendance;

use App\Models\ESBTPAttendance;
use App\Services\AbsenceJustificationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Validation pour la soumission/re-soumission d'une justification par l'étudiant.
 *
 * Authorize via Gate 'submit' routé vers AbsenceJustificationPolicy.
 */
class JustifyAbsenceRequest extends FormRequest
{
    /**
     * Modèle résolu durant authorize(), accessible ensuite via absenceModel().
     */
    public ?ESBTPAttendance $absenceModel = null;

    public function authorize(): bool
    {
        $absence = $this->route('absence');
        // $absence peut être un ID si non model-bound, donc fallback resolution
        if (!$absence instanceof ESBTPAttendance) {
            $absence = ESBTPAttendance::find($this->route('absenceId') ?? $this->route('absence'));
        }
        if (!$absence) {
            return false;
        }
        // Stocke pour réutilisation côté controller via $request->absenceModel()
        $this->absenceModel = $absence;
        return Gate::allows('submit', $absence);
    }

    public function rules(): array
    {
        $maxKb = AbsenceJustificationService::MAX_DOCUMENT_SIZE_KB;
        return [
            'justification' => ['required', 'string', 'min:5', 'max:1000'],
            'document' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                "max:{$maxKb}",
            ],
        ];
    }

    public function messages(): array
    {
        $maxMb = round(AbsenceJustificationService::MAX_DOCUMENT_SIZE_KB / 1024, 1);
        return [
            'justification.required' => 'Une justification est requise.',
            'justification.min' => 'La justification doit contenir au moins 5 caractères.',
            'justification.max' => 'La justification ne peut dépasser 1000 caractères.',
            'document.file' => 'Le fichier joint est invalide.',
            'document.mimes' => 'Le document doit être au format PDF, JPG ou PNG.',
            'document.mimetypes' => 'Le contenu du fichier ne correspond pas à un PDF, JPG ou PNG valide.',
            'document.max' => "Le document ne peut dépasser {$maxMb} Mo.",
        ];
    }

    public function absenceModel(): ESBTPAttendance
    {
        return $this->absenceModel ?? ESBTPAttendance::findOrFail(
            $this->route('absenceId') ?? $this->route('absence')
        );
    }
}
