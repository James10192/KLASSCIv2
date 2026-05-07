<?php

namespace App\Http\Requests;

use App\Models\ESBTPStudentAccessibilityProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccessibilityProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('students.accessibility.edit');
    }

    public function rules(): array
    {
        $categoryKeys = array_keys(ESBTPStudentAccessibilityProfile::CATEGORIES);
        $accommodationKeys = array_keys(ESBTPStudentAccessibilityProfile::ACCOMMODATIONS);

        return [
            'has_official_recognition' => 'sometimes|boolean',
            'recognition_reference'    => 'nullable|string|max:100',

            'categories'   => 'nullable|array',
            'categories.*' => ['string', Rule::in($categoryKeys)],

            'short_description' => 'nullable|string|max:200',
            'full_description'  => 'nullable|string|max:5000',

            'accommodations'   => 'nullable|array',
            'accommodations.*' => ['string', Rule::in($accommodationKeys)],
            'accommodations_notes' => 'nullable|string|max:2000',

            'requires_third_time'    => 'sometimes|boolean',
            'third_time_percentage'  => 'nullable|integer|min:0|max:100',
            'assistant_required'     => 'sometimes|boolean',

            'effective_from' => 'nullable|date',
            'effective_to'   => 'nullable|date|after_or_equal:effective_from',
        ];
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
