<?php

namespace App\Http\Requests\Planning;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanificationRequest extends FormRequest
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
            "annee_universitaire_id" =>
                "required|exists:esbtp_annee_universitaires,id",
            "filiere_id" => "required|exists:esbtp_filieres,id",
            "niveau_etude_id" => "required|exists:esbtp_niveau_etudes,id",
            "matiere_id" => "required|exists:esbtp_matieres,id",
            "semestre" => "required|integer|min:1|max:4",
            "volume_horaire_total" => "required|integer|min:1|max:200",
            "volume_horaire_cm" => "nullable|integer|min:0",
            "volume_horaire_td" => "nullable|integer|min:0",
            "volume_horaire_tp" => "nullable|integer|min:0",
            "coefficient" => "nullable|numeric|min:0.5|max:10",
            "credits_ects" => "nullable|integer|min:1|max:30",
            "enseignant_principal_id" => "nullable|exists:users,id",
            "periode_debut" => "nullable|date",
            "periode_fin" => "nullable|date|after:periode_debut",
            "objectifs_pedagogiques" => "nullable|string|max:1000",
            "prerequis" => "nullable|string|max:500",
            "observations" => "nullable|string|max:500",
        ];
    }
}
