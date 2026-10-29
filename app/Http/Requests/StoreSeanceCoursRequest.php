<?php

namespace App\Http\Requests;

use App\Enums\TypeSeance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeanceCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\ESBTPSeanceCours::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'matiere_id'              => 'required|exists:esbtp_matieres,id',
            // Le champ s'appelle `enseignant_id` mais il porte un
            // `esbtp_teachers.id` : la liste du formulaire est bâtie sur
            // `ESBTPTeacher` et émet `$enseignant->id`. La règle validait contre
            // `users`, ce qui n'atteste rien d'utile — les deux suites
            // d'identifiants sont denses, donc un identifiant de fiche
            // enseignant est presque toujours AUSSI un identifiant d'utilisateur
            // valide, appartenant à quelqu'un d'autre.
            'enseignant_id'           => 'required|exists:esbtp_teachers,id',
            'type_seance'             => ['required', Rule::enum(TypeSeance::class)],
            // Le rang du jour, 1 (lundi) à 6 (samedi) — la même écriture que
            // l'autre écran de création. La règle acceptait `string|max:20`,
            // donc la colonne recevait « Lundi » d'un côté et 1 de l'autre, et
            // toute lecture devait réconcilier les deux. Les lignes déjà
            // écrites en toutes lettres restent lisibles ; ce qui s'écrit
            // désormais ne diverge plus.
            'jour'                    => 'required|integer|min:1|max:6',
            'heure_debut'             => 'required|date_format:H:i',
            'heure_fin'               => 'required|date_format:H:i|after:heure_debut',
            'salle'                   => 'nullable|string|max:50',
            'description'             => 'nullable|string',
            'classe_id'               => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id'  => 'required|exists:esbtp_annee_universitaires,id',
        ];
    }

    public function messages(): array
    {
        return [
            'type_seance.required' => 'Le type de séance est obligatoire.',
            'type_seance.enum'     => 'Le type de séance sélectionné n\'est pas valide.',
        ];
    }
}
