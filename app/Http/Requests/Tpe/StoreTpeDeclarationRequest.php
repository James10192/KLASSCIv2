<?php

namespace App\Http\Requests\Tpe;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPPlanificationAcademique;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreTpeDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tpe.declare') ?? false;
    }

    /**
     * Préparation : forcer semaine_debut au lundi ISO de la date envoyée
     * (déduplication client-side, on évite "mercredi" pour ECUE X de semaine 19).
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('semaine_debut')) {
            try {
                $date = Carbon::parse($this->input('semaine_debut'));
                $this->merge([
                    'semaine_debut' => $date->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
                ]);
            } catch (\Throwable $e) {
                // Laisse la validation rules() rejeter proprement
            }
        }
    }

    public function rules(): array
    {
        $maxHours = (float) SettingsHelper::get('tpe.max_hours_per_week_per_ecue', 10);
        $windowWeeks = (int) SettingsHelper::get('tpe.declaration_window_weeks', 2);
        $earliest = Carbon::now()->startOfWeek(Carbon::MONDAY)->subWeeks($windowWeeks)->format('Y-m-d');
        $latest = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        return [
            'matiere_id' => [
                'required',
                'integer',
                'exists:esbtp_matieres,id',
            ],
            'annee_universitaire_id' => [
                'required',
                'integer',
                'exists:esbtp_annee_universitaires,id',
            ],
            'semaine_debut' => [
                'required',
                'date_format:Y-m-d',
                "after_or_equal:{$earliest}",
                "before_or_equal:{$latest}",
            ],
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
        $windowWeeks = (int) SettingsHelper::get('tpe.declaration_window_weeks', 2);

        return [
            'matiere_id.required' => 'Veuillez choisir une matière (ECUE).',
            'matiere_id.exists' => 'La matière sélectionnée n\'existe pas.',
            'semaine_debut.required' => 'Veuillez choisir la semaine concernée.',
            'semaine_debut.after_or_equal' => "Vous ne pouvez déclarer que les {$windowWeeks} dernières semaines.",
            'semaine_debut.before_or_equal' => 'Vous ne pouvez pas déclarer pour une semaine future.',
            'heures.required' => 'Le nombre d\'heures est obligatoire.',
            'heures.min' => 'Saisissez au moins 0,25 heure (15 minutes).',
            'heures.max' => 'Le plafond hebdomadaire par ECUE est configuré par l\'école.',
            'description.max' => 'La description est limitée à 1000 caractères.',
        ];
    }

    /**
     * Vérifie après validation que la matière (ECUE) est bien planifiée pour
     * la classe de l'étudiant courant — anti-bypass via id arbitraire.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            /** @var \App\Models\User|null $user */
            $user = $this->user();
            $etudiant = ESBTPEtudiant::query()
                ->where('user_id', $user?->id)
                ->first();

            if (! $etudiant) {
                $v->errors()->add('matiere_id', 'Profil étudiant introuvable — contactez la scolarité.');
                return;
            }

            $classe = $etudiant->classe; // hasOneThrough via inscription active
            if (! $classe) {
                $v->errors()->add('matiere_id', 'Aucune inscription active — déclaration impossible.');
                return;
            }

            $planifs = ESBTPPlanificationAcademique::query()
                ->where('filiere_id', $classe->filiere_id)
                ->where('niveau_etude_id', $classe->niveau_etude_id)
                ->where('matiere_id', $this->input('matiere_id'))
                ->where('annee_universitaire_id', $this->input('annee_universitaire_id'))
                ->where('is_active', true)
                ->exists();

            if (! $planifs) {
                $v->errors()->add(
                    'matiere_id',
                    'Cette matière n\'est pas planifiée pour votre classe cette année — choisissez une autre ECUE.'
                );
            }
        });
    }
}
