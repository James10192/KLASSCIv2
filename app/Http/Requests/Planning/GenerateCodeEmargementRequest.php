<?php

namespace App\Http\Requests\Planning;

use Illuminate\Foundation\Http\FormRequest;

class GenerateCodeEmargementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "type" => "required|in:session,journee,personnalise",
            "duree" => "required|integer|min:1|max:72",
            "activation" => "nullable|in:immediate,1,2,4,24",
            "description" => "nullable|string|max:255",
            "seance_id" => "nullable|exists:esbtp_seance_cours,id",
        ];
    }
}
