<?php

namespace App\Http\Requests\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evaluation_id' => ['required', 'integer', 'exists:esbtp_evaluations,id'],
            'entry_mode' => ['required', Rule::in(GradeSheetEntryMode::values())],
            'expected_at' => ['nullable', 'date'],
        ];
    }

    public function entryMode(): GradeSheetEntryMode
    {
        return GradeSheetEntryMode::from($this->string('entry_mode')->toString());
    }
}
