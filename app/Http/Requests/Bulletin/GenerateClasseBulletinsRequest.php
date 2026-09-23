<?php

namespace App\Http\Requests\Bulletin;

use Illuminate\Foundation\Http\FormRequest;

class GenerateClasseBulletinsRequest extends FormRequest
{
    /** Motif d'un bulletin incomplet : aussi exigé par la régénération d'un seul élève. */
    public const REGLE_MOTIF_INCOMPLET = 'nullable|string|min:8|max:1000';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classe_id'              => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'periode'                => 'required|in:semestre1,semestre2,annuel',
            'recalculer'             => 'sometimes|boolean',
            'incomplete_reason'      => self::REGLE_MOTIF_INCOMPLET,
            // Tranche optionnelle : le front decoupe la classe pour tenir dans
            // la limite d'execution de l'hebergement.
            'student_ids'            => 'sometimes|array|max:60',
            'student_ids.*'          => 'integer',
        ];
    }
}
