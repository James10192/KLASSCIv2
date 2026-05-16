<?php

namespace App\Http\Requests\Attendance;

use App\Models\ESBTPAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Validation pour le traitement (approve/reject) d'une justification par admin.
 *
 * Authorize via Gate 'process' routé vers AbsenceJustificationPolicy.
 */
class ProcessJustificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $absence = $this->route('absence');
        if (!$absence instanceof ESBTPAttendance) {
            $absence = ESBTPAttendance::find($this->route('absenceId') ?? $this->route('absence'));
        }
        if (!$absence) {
            return false;
        }
        $this->absenceModel = $absence;
        return Gate::allows('process', $absence);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'admin_comment' => ['required_if:decision,reject', 'nullable', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'La décision est requise.',
            'decision.in' => 'La décision doit être "approve" ou "reject".',
            'admin_comment.required_if' => 'Un commentaire est requis pour justifier le rejet.',
            'admin_comment.min' => 'Le commentaire doit contenir au moins 5 caractères.',
            'admin_comment.max' => 'Le commentaire ne peut dépasser 500 caractères.',
        ];
    }

    public ?ESBTPAttendance $absenceModel = null;

    public function absenceModel(): ESBTPAttendance
    {
        return $this->absenceModel ?? ESBTPAttendance::findOrFail(
            $this->route('absenceId') ?? $this->route('absence')
        );
    }
}
