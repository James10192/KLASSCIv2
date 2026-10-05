<?php

namespace App\Domain\Assistant\Actions\Enseignants;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Enums\TeacherRegime;
use App\Models\ESBTPTeacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ModifierEnseignant extends ActionAgent
{
    public function cle(): string { return 'modification_enseignant'; }

    public function libelle(): string { return 'Préparation de la modification de l’enseignant…'; }

    public function description(): string
    {
        return 'PROPOSE de modifier le profil d’un enseignant existant à partir de son identifiant exact retourné par search_teachers. '
            .'Peut modifier nom, téléphone, email, titre, grade, spécialisation, régime, statut, date de début et charge hebdomadaire. '
            .'Le taux horaire n’est proposé que si l’utilisateur possède comptabilite.salaires.set_rate. Ne devine jamais un enseignant à partir d’un nom ambigu.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'enseignant_id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'phone' => ['type' => 'string'],
                'email' => ['type' => 'string'],
                'titre_academique' => ['type' => 'string'],
                'grade_academique' => ['type' => 'string'],
                'specialization' => ['type' => 'string'],
                'regime' => ['type' => 'string', 'enum' => TeacherRegime::values()],
                'status' => ['type' => 'string', 'enum' => ['active', 'inactive']],
                'date_debut_activite' => ['type' => 'string'],
                'charge_horaire_max_semaine' => ['type' => 'integer'],
                'taux_horaire' => ['type' => 'number'],
            ],
            'required' => ['enseignant_id'],
        ];
    }

    public function isAvailableFor($user): bool
    {
        return (bool) config('assistant.actions.actives', true) && $user?->can('teachers.edit');
    }

    public function preparer(array $args, $user): Proposition
    {
        $teacher = ESBTPTeacher::with('user')->find((int) ($args['enseignant_id'] ?? 0));
        if (! $teacher || ! $teacher->user) {
            return new Proposition(titre: 'Modifier un enseignant', resume: '', manques: ['Enseignant introuvable. Recherche-le d’abord avec search_teachers.']);
        }

        $modifs = array_filter($args, fn ($v, $k) => $k !== 'enseignant_id' && $v !== null, ARRAY_FILTER_USE_BOTH);
        if ($modifs === []) {
            return new Proposition(titre: 'Modifier '.$teacher->name, resume: '', manques: ['Quelle information faut-il modifier ?']);
        }
        if (array_key_exists('taux_horaire', $modifs) && ! $user->can('comptabilite.salaires.set_rate')) {
            return new Proposition(titre: 'Modifier '.$teacher->name, resume: '', manques: ['Vous n’avez pas le droit de modifier le taux horaire.']);
        }

        $validator = Validator::make($modifs, [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher->user_id)],
            'titre_academique' => 'sometimes|nullable|string|max:10',
            'grade_academique' => 'sometimes|nullable|string|max:50',
            'specialization' => 'sometimes|required|string|max:255',
            'regime' => ['sometimes', Rule::in(TeacherRegime::values())],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'date_debut_activite' => 'sometimes|nullable|date',
            'charge_horaire_max_semaine' => 'sometimes|nullable|integer|min:1|max:60',
            'taux_horaire' => 'sometimes|nullable|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return new Proposition(titre: 'Modifier '.$teacher->name, resume: '', manques: $validator->errors()->all());
        }

        $avant = $this->snapshot($teacher);
        $lignes = [];
        foreach ($modifs as $champ => $valeur) {
            $ancien = $avant[$champ] ?? null;
            if ((string) $ancien !== (string) $valeur) {
                $lignes[] = [$champ, (string) ($ancien ?? '—'), (string) ($valeur ?? '—')];
            }
        }
        if ($lignes === []) {
            return Proposition::sansObjet('Modifier '.$teacher->name, 'les valeurs demandées sont déjà enregistrées.');
        }

        return new Proposition(
            titre: 'Modifier '.$teacher->name,
            resume: count($lignes).' information(s) seront modifiée(s).',
            tableau: ['colonnes' => ['Champ', 'Avant', 'Après'], 'lignes' => $lignes],
            donnees: ['enseignant_id' => $teacher->id, 'modifs' => $modifs],
            etat: ['avant' => $avant],
            risque: 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('teachers.edit')) {
            throw new PropositionPerimee('Vous n’avez plus le droit de modifier les enseignants.');
        }
        $teacher = ESBTPTeacher::with('user')->find((int) $proposition->donnees['enseignant_id']);
        if (! $teacher || ! $teacher->user || $this->snapshot($teacher) !== $proposition->etat['avant']) {
            throw new PropositionPerimee('Le profil de l’enseignant a changé depuis la proposition.');
        }

        DB::transaction(function () use ($teacher, $proposition, $user) {
            $m = $proposition->donnees['modifs'];
            $userData = [];
            foreach (['name', 'phone', 'email'] as $champ) {
                if (array_key_exists($champ, $m)) $userData[$champ] = $m[$champ];
            }
            if ($userData !== []) $teacher->user->update($userData);

            $teacherData = [];
            $map = [
                'titre_academique' => 'title', 'grade_academique' => 'grade', 'specialization' => 'specialization',
                'regime' => 'regime', 'status' => 'status', 'date_debut_activite' => 'date_debut_activite',
                'charge_horaire_max_semaine' => 'teaching_hours_due', 'taux_horaire' => 'taux_horaire',
            ];
            foreach ($map as $entree => $colonne) {
                if (array_key_exists($entree, $m)) $teacherData[$colonne] = $m[$entree];
            }
            $teacherData['updated_by'] = $user->id;
            $teacher->update($teacherData);
        });

        return ['message' => 'Enseignant modifié.', 'lien' => route('esbtp.enseignants.edit', ['enseignant' => $teacher->id], false), 'model_type' => ESBTPTeacher::class, 'model_id' => $teacher->id];
    }

    private function snapshot(ESBTPTeacher $teacher): array
    {
        return [
            'name' => $teacher->user?->name, 'phone' => $teacher->user?->phone, 'email' => $teacher->user?->email,
            'titre_academique' => $teacher->title, 'grade_academique' => $teacher->grade, 'specialization' => $teacher->specialization,
            'regime' => $teacher->regime, 'status' => $teacher->status,
            'date_debut_activite' => optional($teacher->date_debut_activite)->format('Y-m-d'),
            'charge_horaire_max_semaine' => $teacher->teaching_hours_due, 'taux_horaire' => $teacher->taux_horaire,
        ];
    }
}
