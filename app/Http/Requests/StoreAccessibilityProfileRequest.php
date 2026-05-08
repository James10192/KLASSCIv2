<?php

namespace App\Http\Requests;

use App\Models\ESBTPStudentAccessibilityProfile;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccessibilityProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('students.accessibility.edit');
    }

    public function rules(): array
    {
        return ESBTPStudentAccessibilityProfile::validationRules();
    }

    public function messages(): array
    {
        return [
            'effective_to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'short_description.max'       => 'Le résumé ne doit pas dépasser 200 caractères (visible aux enseignants).',
            'third_time_percentage.max'   => 'Le pourcentage de tiers-temps ne peut pas dépasser 100%.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_official_recognition' => $this->boolean('has_official_recognition'),
            'requires_third_time'      => $this->boolean('requires_third_time'),
            'assistant_required'       => $this->boolean('assistant_required'),
        ]);
    }
}
