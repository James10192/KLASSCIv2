<?php

namespace App\Http\Requests\AcademicPilotage;

use Illuminate\Foundation\Http\FormRequest;

class SyncGradeSheetEntriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expected_lock_version' => ['required', 'integer', 'min:1'],
        ];
    }
}
