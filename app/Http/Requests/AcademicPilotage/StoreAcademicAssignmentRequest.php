<?php

namespace App\Http\Requests\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\AcademicResponsibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'classe_id' => ['required', 'integer', 'exists:esbtp_classes,id'],
            'annee_universitaire_id' => [
                'required',
                'integer',
                'exists:esbtp_annee_universitaires,id',
            ],
            'responsibility' => ['required', Rule::in(AcademicResponsibility::values())],
            'metadata' => ['sometimes', 'array'],
        ];
    }

    public function responsibility(): AcademicResponsibility
    {
        return AcademicResponsibility::from(
            $this->string('responsibility')->toString()
        );
    }
}
