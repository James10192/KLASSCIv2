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
 * Modification ciblée d'une fiche enseignant par Nanan.
 *
 * Le professeur d'un ECUE LMD reste une autre action : ici on corrige le profil
 * RH/pédagogique de la personne (nom, titre, grade, spécialité, régime...).
 */
class ModifierProfilEnseignant extends ActionAgent
{
    public function __construct(private readonly UserManagementService $gestionUtilisateurs) {}

    public function cle(): string
    {
        return 'profil_enseignant';
    }

    public function libelle(): string
    {
        return 'Préparation de la fiche enseignant…';
    }

    /**
     * Action ajoutée progressivement au registre : sa garde est explicite ici
     * plutôt que d'ouvrir un outil sans configuration. La matrice canManage est
     * ensuite revérifiée sur la cible exacte dans preparer() et executer().
     */
    public function isAvailableFor($user): bool
    {
        return (bool) config('assistant.actions.actives', true)
            && $user
            && $user->can('teachers.edit');
    }

    public function description(): string
    {
        return 'PROPOSE de modifier une fiche enseignant existante identifiée par teacher_id retourné par search_teachers : nom, email, téléphone, titre académique, grade, spécialisation, régime, date de début, diplôme, université, année du diplôme, bio, site web, statut et charge hebdomadaire. '
            .'Ne confonds pas cette action avec proposer_professeur_classe_lmd, qui change le professeur d’un ECUE dans une classe. Le taux horaire n’est accepté que si la personne connectée a le droit financier correspondant.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'teacher_id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'email' => ['type' => ['string', 'null']],
                'phone' => ['type' => ['string', 'null']],
                'titre_academique' => ['type' => ['string', 'null']],
                'grade_academique' => ['type' => ['string', 'null']],
                'specialization' => ['type' => 'string'],
                'regime' => ['type' => 'string', 'enum' => TeacherRegime::values()],
                'date_debut_activite' => ['type' => ['string', 'null']],
                'diplome_principal' => ['type' => ['string', 'null']],
                'universite_diplome' => ['type' => ['string', 'null']],
                'annee_diplome' => ['type' => ['integer', 'null']],
                'bio' => ['type' => ['string', 'null']],
                'website' => ['type' => ['string', 'null']],
                'status' => ['type' => 'string', 'enum' => ['active', 'inactive']],
                'charge_horaire_max_semaine' => ['type' => ['integer', 'null']],
                'taux_horaire' => ['type' => ['number', 'null']],
            ],
            'required' => ['teacher_id'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $id = filter_var($args['teacher_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null) {
            return new Proposition(titre: 'Modifier une fiche enseignant', resume: '', manques: ['Quel enseignant précis ? Recherche-le d’abord avec search_teachers et utilise son teacher_id.']);
        }

        $teacher = ESBTPTeacher::with('user')->find($id);
        if (! $teacher?->user) {
            return new Proposition(titre: 'Modifier une fiche enseignant', resume: '', manques: ['Cet enseignant n’existe plus. Relance search_teachers.']);
        }
        if (! $user->can('teachers.edit') || ! $this->gestionUtilisateurs->canManage($user, $teacher->user)) {
            return new Proposition(titre: 'Modifier une fiche enseignant', resume: '', manques: ['Vous n’avez pas le droit de modifier cet enseignant.']);
        }

        $champs = array_intersect_key($args, array_flip([
            'name', 'email', 'phone', 'titre_academique', 'grade_academique', 'specialization', 'regime',
            'date_debut_activite', 'diplome_principal', 'universite_diplome', 'annee_diplome', 'bio', 'website',
            'status', 'charge_horaire_max_semaine', 'taux_horaire',
        ]));
        if ($champs === []) {
            return new Proposition(titre: 'Modifier '.$teacher->user->name, resume: '', manques: ['Quelle information faut-il modifier ?']);
        }
        if (array_key_exists('taux_horaire', $champs) && ! $user->can('comptabilite.salaires.set_rate')) {
            return new Proposition(titre: 'Modifier '.$teacher->user->name, resume: '', manques: ['Vous n’avez pas le droit financier nécessaire pour modifier le taux horaire. Les autres informations peuvent être proposées séparément.']);
        }

        $validator = Validator::make($champs, [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher->user_id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'titre_academique' => ['sometimes', 'nullable', 'string', 'max:10'],
            'grade_academique' => ['sometimes', 'nullable', 'string', 'max:50'],
            'specialization' => ['sometimes', 'string', 'max:255'],
            'regime' => ['sometimes', Rule::in(TeacherRegime::values())],
            'date_debut_activite' => ['sometimes', 'nullable', 'date'],
            'diplome_principal' => ['sometimes', 'nullable', 'string', 'max:255'],
            'universite_diplome' => ['sometimes', 'nullable', 'string', 'max:255'],
            'annee_diplome' => ['sometimes', 'nullable', 'integer', 'min:1950', 'max:'.date('Y')],
            'bio' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'charge_horaire_max_semaine' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:60'],
            'taux_horaire' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ]);
        if ($validator->fails()) {
            return new Proposition(titre: 'Modifier '.$teacher->user->name, resume: '', manques: $validator->errors()->all());
        }
        $champs = $validator->validated();

        $avant = $this->snapshot($teacher);
        $lignes = [];
        foreach ($champs as $cle => $valeur) {
            $ancienne = $avant[$cle] ?? null;
            if ((string) $ancienne !== (string) $valeur) {
                $lignes[] = [$this->libelleChamp($cle), $ancienne === null || $ancienne === '' ? '—' : (string) $ancienne, $valeur === null || $valeur === '' ? '—' : (string) $valeur];
            }
        }
        if ($lignes === []) {
            return Proposition::sansObjet('Modifier '.$teacher->user->name, 'Les valeurs demandées sont déjà enregistrées.');
        }

        return new Proposition(
            titre: 'Modifier la fiche de '.$teacher->user->name,
            resume: count($lignes).' information(s) seront modifiées sur le profil enseignant.',
            tableau: ['colonnes' => ['Champ', 'Actuel', 'Après'], 'lignes' => $lignes],
            avertissements: array_key_exists('regime', $champs) && $champs['regime'] !== TeacherRegime::Permanent->value
                ? ['Un régime non permanent ramène la charge hebdomadaire de référence à 0, comme le formulaire enseignant.'] : [],
            donnees: ['teacher_id' => (int) $teacher->id, 'champs' => $champs],
            etat: $avant,
            risque: array_key_exists('taux_horaire', $champs) ? 'eleve' : 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $teacher = ESBTPTeacher::with('user')->find($proposition->donnees['teacher_id']);
        if (! $teacher?->user || ! $user->can('teachers.edit') || ! $this->gestionUtilisateurs->canManage($user, $teacher->user)) {
            throw new PropositionPerimee('La fiche enseignant n’est plus modifiable avec vos droits actuels.');
        }
        $champs = $proposition->donnees['champs'];
        if (array_key_exists('taux_horaire', $champs) && ! $user->can('comptabilite.salaires.set_rate')) {
            throw new PropositionPerimee('Le droit de modifier le taux horaire n’est plus disponible.');
        }
        if ($this->snapshot($teacher) !== $proposition->etat) {
            throw new PropositionPerimee('La fiche enseignant a changé depuis la proposition. Relisez-la avant de valider.');
        }

        DB::transaction(function () use ($teacher, $champs, $user) {
            $userData = [];
            foreach (['name', 'email', 'phone'] as $cle) {
                if (array_key_exists($cle, $champs)) $userData[$cle] = $champs[$cle] ?: null;
            }
            if ($userData !== []) $teacher->user->update($userData);

            $map = [
                'titre_academique' => 'title', 'grade_academique' => 'grade', 'specialization' => 'specialization',
                'regime' => 'regime', 'date_debut_activite' => 'date_debut_activite', 'diplome_principal' => 'diplome_principal',
                'universite_diplome' => 'universite_diplome', 'annee_diplome' => 'annee_diplome', 'bio' => 'bio',
                'website' => 'website', 'status' => 'status', 'taux_horaire' => 'taux_horaire',
            ];
            $teacherData = ['updated_by' => $user->id];
            foreach ($map as $entree => $colonne) {
                if (array_key_exists($entree, $champs)) $teacherData[$colonne] = $champs[$entree];
            }
            $regime = $champs['regime'] ?? $teacher->regime ?? TeacherRegime::Vacataire->value;
            if (array_key_exists('regime', $champs) || array_key_exists('charge_horaire_max_semaine', $champs)) {
                $teacherData['teaching_hours_due'] = $regime === TeacherRegime::Permanent->value
                    ? ($champs['charge_horaire_max_semaine'] ?? $teacher->teaching_hours_due ?? 18)
                    : 0;
            }
            $teacherData['is_active'] = ($champs['status'] ?? $teacher->status) === 'active';
            $teacher->update($teacherData);
        });

        $teacher->refresh()->load('user');
        return [
            'message' => 'Fiche enseignant mise à jour : '.$teacher->user->name.'.',
            'lien' => Route::has('esbtp.enseignants.show') ? route('esbtp.enseignants.show', ['enseignant' => $teacher->id], false) : null,
            'model_type' => ESBTPTeacher::class,
            'model_id' => $teacher->id,
            'details' => ['teacher_id' => $teacher->id, 'champs' => array_keys($champs)],
        ];
    }

    private function snapshot(ESBTPTeacher $teacher): array
    {
        return [
            'name' => $teacher->user?->name,
            'email' => $teacher->user?->email,
            'phone' => $teacher->user?->phone,
            'titre_academique' => $teacher->title,
            'grade_academique' => $teacher->grade,
            'specialization' => $teacher->specialization,
            'regime' => $teacher->regime,
            'date_debut_activite' => optional($teacher->date_debut_activite)->format('Y-m-d'),
            'diplome_principal' => $teacher->diplome_principal,
            'universite_diplome' => $teacher->universite_diplome,
            'annee_diplome' => $teacher->annee_diplome,
            'bio' => $teacher->bio,
            'website' => $teacher->website,
            'status' => $teacher->status,
            'charge_horaire_max_semaine' => (int) ($teacher->teaching_hours_due ?? 0),
            'taux_horaire' => $teacher->taux_horaire !== null ? (float) $teacher->taux_horaire : null,
        ];
    }

    private function libelleChamp(string $cle): string
    {
        return [
            'name' => 'Nom', 'email' => 'Email', 'phone' => 'Téléphone', 'titre_academique' => 'Titre académique',
            'grade_academique' => 'Grade académique', 'specialization' => 'Spécialisation', 'regime' => 'Régime',
            'date_debut_activite' => 'Début d’activité', 'diplome_principal' => 'Diplôme principal',
            'universite_diplome' => 'Université / institut', 'annee_diplome' => 'Année du diplôme', 'bio' => 'Biographie',
            'website' => 'Site web', 'status' => 'Statut', 'charge_horaire_max_semaine' => 'Charge hebdomadaire',
            'taux_horaire' => 'Taux horaire',
        ][$cle] ?? $cle;
    }
}
