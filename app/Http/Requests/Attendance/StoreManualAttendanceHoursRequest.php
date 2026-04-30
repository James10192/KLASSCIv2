<?php

namespace App\Http\Requests\Attendance;

use App\Helpers\SettingsHelper;
use App\Services\ESBTP\ManualAttendanceHoursService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreManualAttendanceHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attendances.create') ?? false;
    }

    public function rules(): array
    {
        $globalEnabled = (bool) SettingsHelper::get('attendance_manual_hours_global_enabled', false);

        return [
            'classe_id' => ['required', 'integer', 'exists:esbtp_classes,id'],
            // `matiere_id` devient optionnel uniquement si la feature
            // globale est activée côté tenant. Sinon on garde le
            // comportement historique (matière obligatoire).
            'matiere_id' => $globalEnabled
                ? ['nullable', 'integer', 'exists:esbtp_matieres,id']
                : ['required', 'integer', 'exists:esbtp_matieres,id'],
            'annee_universitaire_id' => ['required', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'periode' => ['required', 'string', Rule::in(ManualAttendanceHoursService::PERIODES)],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.etudiant_id' => ['required', 'integer', 'exists:esbtp_etudiants,id'],
            'entries.*.heures_presence' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'entries.*.heures_absence_justifiees' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'entries.*.heures_absence_non_justifiees' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'entries.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'entries.required' => 'Aucune saisie à enregistrer.',
            'entries.*.etudiant_id.exists' => 'Un étudiant sélectionné n\'existe pas.',
            'entries.*.heures_presence.numeric' => 'Les heures de présence doivent être un nombre.',
            'entries.*.heures_presence.min' => 'Les heures de présence ne peuvent pas être négatives.',
            'entries.*.heures_absence_justifiees.numeric' => 'Les heures d\'absence justifiées doivent être un nombre.',
            'entries.*.heures_absence_non_justifiees.numeric' => 'Les heures d\'absence non justifiées doivent être un nombre.',
            'periode.in' => 'La période doit être semestre1, semestre2 ou annuel.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
