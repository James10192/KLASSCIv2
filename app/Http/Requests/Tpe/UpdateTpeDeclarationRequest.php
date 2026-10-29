<?php

namespace App\Http\Requests\Tpe;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPTpeDeclaration;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTpeDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->can('tpe.declare')) {
            return false;
        }

        /** @var ESBTPTpeDeclaration|null $declaration */
        $declaration = $this->route('declaration') ?? $this->route('tpe_journal');
        if (! $declaration instanceof ESBTPTpeDeclaration) {
            return false;
        }

        // Ownership : la déclaration appartient à l'étudiant courant
        $isOwner = $declaration->etudiant
            && $declaration->etudiant->user_id === $user->id;

        if (! $isOwner) {
            return false;
        }

        // L'étudiant ne peut modifier que les déclarations encore actionnables
        // par lui (statut EN_ATTENTE en mode Option 3 — Option 2 : tout est VALIDE
        // donc plus modifiable une fois soumis).
        return $declaration->statut->isEditableByStudent();
    }

    public function rules(): array
    {
        $maxHours = (float) SettingsHelper::get('tpe.max_hours_per_week_per_ecue', 10);

        return [
            'heures' => [
                'required',
                'numeric',
                'min:0.25',
                "max:{$maxHours}",
            ],
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'heures.required' => 'Le nombre d\'heures est obligatoire.',
            'heures.min' => 'Saisissez au moins 0,25 heure.',
            'heures.max' => 'Le plafond hebdomadaire par ECUE est configuré par l\'école.',
            'description.max' => 'La description est limitée à 1000 caractères.',
        ];
    }
}
