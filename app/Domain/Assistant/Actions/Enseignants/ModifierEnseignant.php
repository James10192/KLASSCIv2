<?php

namespace App\Domain\Assistant\Actions\Enseignants;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Enums\TeacherRegime;
use App\Models\ESBTPTeacher;
use App\Services\UserManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Modification sûre de la fiche professeur. Les rôles, mots de passe, droits,
 * disponibilités et tarifs restent hors de cette action.
 */
class ModifierEnseignant extends ActionAgent
{
    private const CHAMPS = [
        'nom', 'email', 'telephone', 'titre_academique', 'grade_academique',
        'specialisation', 'regime', 'charge_horaire_max_semaine', 'date_debut_activite',
        'diplome_principal', 'universite_diplome', 'annee_diplome', 'bio', 'website',
    ];

    public function __construct(private readonly UserManagementService $users) {}

    public function cle(): string
    {
        return 'modification_enseignant';
    }

    public function description(): string
    {
        return "PROPOSE de modifier la fiche d'un enseignant existant trouvé avec search_teachers : nom, contact, titre/grade académique, spécialisation, régime, charge hebdomadaire, date d'activité, diplôme, bio ou site web. Ne touche jamais aux rôles, droits, mot de passe, disponibilités ni tarifs. Le titre académique est celui repris devant le nom sur les bulletins LMD.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'enseignant_id' => ['type' => 'integer'],
                'changements' => [
                    'type' => 'object',
                    'properties' => [
                        'nom' => ['type' => 'string'],
                        'email' => ['type' => ['string', 'null']],
                        'telephone' => ['type' => ['string', 'null']],
                        'titre_academique' => ['type' => ['string', 'null']],
                        'grade_academique' => ['type' => ['string', 'null']],
                        'specialisation' => ['type' => 'string'],
                        'regime' => ['type' => 'string', 'enum' => TeacherRegime::values()],
                        'charge_horaire_max_semaine' => ['type' => ['integer', 'null']],
                        'date_debut_activite' => ['type' => ['string', 'null']],
                        'diplome_principal' => ['type' => ['string', 'null']],
                        'universite_diplome' => ['type' => ['string', 'null']],
                        'annee_diplome' => ['type' => ['integer', 'null']],
                        'bio' => ['type' => ['string', 'null']],
                        'website' => ['type' => ['string', 'null']],
                    ],
                ],
            ],
            'required' => ['enseignant_id', 'changements'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $teacher = ESBTPTeacher::with('user')->find((int) ($args['enseignant_id'] ?? 0));
        if (! $teacher || ! $teacher->user) {
            return new Proposition('Modifier un enseignant', '', manques: ['Enseignant introuvable. Utilisez search_teachers et transmettez son ID enseignant.']);
        }
        if (! $this->users->canManage($user, $teacher->user)) {
            return new Proposition('Modifier un enseignant', '', manques: ["Vous ne pouvez pas gérer le compte de cet enseignant."]);
        }

        $changements = array_intersect_key((array) ($args['changements'] ?? []), array_flip(self::CHAMPS));
        if ($changements === []) {
            return new Proposition('Modifier un enseignant', '', manques: ['Indiquez au moins un champ à modifier.']);
        }

        $validation = Validator::make($changements, $this->regles($teacher));
        if ($validation->fails()) {
            return new Proposition('Modifier un enseignant', '', manques: $validation->errors()->all());
        }
        $changements = $validation->validated();

        $avant = $this->valeurs($teacher);
        $effectifs = [];
        $lignes = [];
        foreach ($changements as $cle => $apres) {
            $apres = is_string($apres) ? trim($apres) : $apres;
            $apres = $apres === '' && $cle !== 'specialisation' && $cle !== 'nom' ? null : $apres;
            if (($avant[$cle] ?? null) == $apres) {
                continue;
            }
            $effectifs[$cle] = $apres;
            $lignes[] = [$this->libelle($cle), $this->afficher($avant[$cle] ?? null), $this->afficher($apres)];
        }

        if ($effectifs === []) {
            return Proposition::sansObjet('Modifier un enseignant', 'La fiche porte déjà les valeurs demandées.');
        }

        return new Proposition(
            titre: 'Modifier '.$teacher->user->name,
            resume: count($effectifs).' information(s) de la fiche enseignant seront modifiées.',
            tableau: ['colonnes' => ['Champ', 'Avant', 'Après'], 'lignes' => $lignes],
            donnees: ['enseignant_id' => (int) $teacher->id, 'changements' => $effectifs],
            etat: ['teacher_updated_at' => (string) $teacher->updated_at, 'user_updated_at' => (string) $teacher->user->updated_at],
            avertissements: array_key_exists('titre_academique', $effectifs)
                ? ['Le nouveau titre sera repris sur les futurs snapshots de bulletins LMD ; un bulletin déjà publié reste figé.'] : [],
            risque: 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('teachers.edit')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de modifier les enseignants.");
        }

        $teacher = ESBTPTeacher::with('user')->find($proposition->donnees['enseignant_id']);
        if (! $teacher || ! $teacher->user || ! $this->users->canManage($user, $teacher->user)) {
            throw new PropositionPerimee("L'enseignant n'est plus modifiable par ce compte.");
        }
        if ((string) $teacher->updated_at !== $proposition->etat['teacher_updated_at'] || (string) $teacher->user->updated_at !== $proposition->etat['user_updated_at']) {
            throw new PropositionPerimee('La fiche enseignant a changé depuis la proposition.');
        }

        $c = $proposition->donnees['changements'];
        $validation = Validator::make($c, $this->regles($teacher));
        if ($validation->fails()) {
            throw new PropositionPerimee(implode(' ', $validation->errors()->all()));
        }

        DB::transaction(function () use ($teacher, $c, $user): void {
            $userFields = [];
            foreach (['nom' => 'name', 'email' => 'email', 'telephone' => 'phone'] as $cle => $colonne) {
                if (array_key_exists($cle, $c)) $userFields[$colonne] = $c[$cle];
            }
            if ($userFields !== []) $teacher->user->update($userFields);

            $teacherFields = ['updated_by' => (int) $user->id];
            $map = [
                'titre_academique' => 'title', 'grade_academique' => 'grade', 'specialisation' => 'specialization',
                'regime' => 'regime', 'date_debut_activite' => 'date_debut_activite', 'diplome_principal' => 'diplome_principal',
                'universite_diplome' => 'universite_diplome', 'annee_diplome' => 'annee_diplome', 'bio' => 'bio', 'website' => 'website',
            ];
            foreach ($map as $cle => $colonne) if (array_key_exists($cle, $c)) $teacherFields[$colonne] = $c[$cle];

            $regime = (string) ($c['regime'] ?? $teacher->regime ?? TeacherRegime::Vacataire->value);
            if (array_key_exists('charge_horaire_max_semaine', $c) || array_key_exists('regime', $c)) {
                $teacherFields['teaching_hours_due'] = $regime === TeacherRegime::Permanent->value
                    ? (int) ($c['charge_horaire_max_semaine'] ?? ($teacher->teaching_hours_due > 0 ? $teacher->teaching_hours_due : 18))
                    : 0;
            }
            $teacher->update($teacherFields);
        });

        return [
            'message' => 'Fiche enseignant mise à jour : '.$teacher->user->fresh()->name.'.',
            'lien' => Route::has('esbtp.enseignants.show') ? route('esbtp.enseignants.show', ['enseignant' => $teacher->id], false) : null,
            'model_type' => ESBTPTeacher::class,
            'model_id' => $teacher->id,
            'details' => ['champs' => array_keys($c)],
        ];
    }

    private function regles(ESBTPTeacher $teacher): array
    {
        return [
            'nom' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher->user_id)],
            'telephone' => 'sometimes|nullable|string|max:20',
            'titre_academique' => 'sometimes|nullable|string|max:10',
            'grade_academique' => 'sometimes|nullable|string|max:50',
            'specialisation' => 'sometimes|required|string|max:255',
            'regime' => ['sometimes', Rule::in(TeacherRegime::values())],
            'charge_horaire_max_semaine' => 'sometimes|nullable|integer|min:1|max:60',
            'date_debut_activite' => 'sometimes|nullable|date',
            'diplome_principal' => 'sometimes|nullable|string|max:255',
            'universite_diplome' => 'sometimes|nullable|string|max:255',
            'annee_diplome' => 'sometimes|nullable|integer|min:1950|max:'.date('Y'),
            'bio' => 'sometimes|nullable|string|max:1000',
            'website' => 'sometimes|nullable|url|max:255',
        ];
    }

    private function valeurs(ESBTPTeacher $t): array
    {
        return [
            'nom' => $t->user->name, 'email' => $t->user->email, 'telephone' => $t->user->phone,
            'titre_academique' => $t->title, 'grade_academique' => $t->grade, 'specialisation' => $t->specialization,
            'regime' => $t->regime, 'charge_horaire_max_semaine' => (int) $t->teaching_hours_due,
            'date_debut_activite' => $t->date_debut_activite?->format('Y-m-d'), 'diplome_principal' => $t->diplome_principal,
            'universite_diplome' => $t->universite_diplome, 'annee_diplome' => $t->annee_diplome,
            'bio' => $t->bio, 'website' => $t->website,
        ];
    }

    private function libelle(string $c): string
    {
        return [
            'nom' => 'Nom', 'email' => 'E-mail', 'telephone' => 'Téléphone', 'titre_academique' => 'Titre académique',
            'grade_academique' => 'Grade académique', 'specialisation' => 'Spécialisation', 'regime' => 'Régime',
            'charge_horaire_max_semaine' => 'Charge hebdomadaire', 'date_debut_activite' => "Date de début d'activité",
            'diplome_principal' => 'Diplôme principal', 'universite_diplome' => 'Université / Institut',
            'annee_diplome' => "Année d'obtention", 'bio' => 'Biographie', 'website' => 'Site web',
        ][$c] ?? $c;
    }

    private function afficher(mixed $v): string
    {
        if ($v === null || $v === '') return '—';
        return mb_strimwidth((string) $v, 0, 90, '…');
    }
}
