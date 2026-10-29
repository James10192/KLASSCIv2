<?php

namespace App\Http\Requests\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionGradeSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(GradeSheetAction::values())],
            'expected_lock_version' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'metadata' => ['sometimes', 'array'],
        ];
    }

    public function action(): GradeSheetAction
    {
        return GradeSheetAction::from($this->string('action')->toString());
    }
}
