<?php

namespace App\Domain\Assistant\Actions\Lmd;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPTeacher;
use App\Services\LMD\EnseignantDeClasseLmd;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/**
 * Confirme le professeur réellement chargé d'un ECUE dans UNE classe LMD.
 * C'est la même source que les évaluations, séances et bulletins LMD ; on ne
 * maintient donc aucun nom parallèle « juste pour le PDF ».
 */
class ConfirmerProfesseurClasseLmd extends ActionAgent
{
    public function __construct(private readonly EnseignantDeClasseLmd $enseignants) {}

    public function cle(): string
    {
        return 'professeur_classe_lmd';
    }

    public function libelle(): string
    {
        return 'Préparation du professeur LMD…';
    }

    public function description(): string
    {
        return 'PROPOSE de confirmer ou changer le professeur réel d’un ECUE pour une classe LMD, une année et un semestre précis. '
            .'Utilise classe_id, matiere_id/ECUE, annee_universitaire_id et teacher_id obtenus avec les outils de lecture. '
            .'À la validation, le service LMD canonique harmonise les évaluations et séances de cette classe et ajoute le professeur au pool du planning si nécessaire. Les bulletins déjà publiés restent figés.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe_id' => ['type' => 'integer'],
                'matiere_id' => ['type' => 'integer', 'description' => 'ID de l’ECUE/matière.'],
                'annee_universitaire_id' => ['type' => 'integer'],
                'semestre' => ['type' => 'integer'],
                'teacher_id' => ['type' => 'integer', 'description' => 'ID du profil enseignant retourné par search_teachers.'],
            ],
            'required' => ['classe_id', 'matiere_id', 'annee_universitaire_id', 'semestre', 'teacher_id'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $ids = [];
        foreach (['classe_id', 'matiere_id', 'annee_universitaire_id', 'semestre', 'teacher_id'] as $cle) {
            $v = filter_var($args[$cle] ?? null, FILTER_VALIDATE_INT);
            if ($v === false || $v === null || $v < 1) {
                return new Proposition(titre: 'Modifier le professeur LMD', resume: '', manques: ["Il manque {$cle} : lis d’abord la classe, l’ECUE, l’année et l’enseignant exacts."]);
            }
            $ids[$cle] = (int) $v;
        }

        $classe = ESBTPClasse::find($ids['classe_id']);
        $matiere = ESBTPMatiere::find($ids['matiere_id']);
        $annee = ESBTPAnneeUniversitaire::find($ids['annee_universitaire_id']);
        $teacher = ESBTPTeacher::with('user')->find($ids['teacher_id']);
        if (! $classe || ! $matiere || ! $annee || ! $teacher?->user) {
            return new Proposition(titre: 'Modifier le professeur LMD', resume: '', manques: ['La classe, l’ECUE, l’année ou l’enseignant n’existe plus. Relis les données avant de proposer.']);
        }

        $resolution = $this->enseignants->resoudre($classe, $matiere->id, $annee->id, $ids['semestre']);
        if (! ($resolution['dans_maquette'] ?? false)) {
            return new Proposition(titre: 'Modifier le professeur LMD', resume: '', manques: [$resolution['message'] ?? 'Cet ECUE n’appartient pas à cette classe/ce semestre.']);
        }

        $actuelId = (int) ($resolution['enseignant_id'] ?? 0);
        if ($actuelId === (int) $teacher->user_id && ! ($resolution['conflit'] ?? false)) {
            return Proposition::sansObjet('Modifier le professeur LMD', $teacher->user->name.' est déjà le professeur résolu pour cet ECUE dans cette classe.');
        }

        $evals = collect($resolution['details']['evaluations'] ?? []);
        $seances = collect($resolution['details']['seances'] ?? []);
        $publies = ESBTPLMDBulletin::query()
            ->where('classe_id', $classe->id)
            ->where('annee_universitaire_id', $annee->id)
            ->where('semestre', $ids['semestre'])
            ->where('is_published', true)
            ->count();

        $avertissements = [];
        if ($evals->isNotEmpty() || $seances->isNotEmpty()) {
            $avertissements[] = sprintf('%d évaluation(s) et %d séance(s) existantes seront harmonisées sur ce professeur.', $evals->count(), $seances->count());
        }
        if ($publies > 0) {
            $avertissements[] = "{$publies} bulletin(s) déjà publié(s) restent figés avec leur snapshot actuel. Dépublier/régénérer est un geste séparé.";
        }
        if ($resolution['conflit'] ?? false) {
            $avertissements[] = 'Des traces contradictoires existent actuellement : cette confirmation les remettra d’accord.';
        }

        return new Proposition(
            titre: 'Confirmer '.$teacher->user->name.' comme professeur',
            resume: sprintf('%s · S%d · %s (%s) : %s → %s.', $classe->name, $ids['semestre'], $matiere->name, $matiere->code ?? 'ECUE', $resolution['enseignant_nom'] ?: 'non défini', $teacher->user->name),
            tableau: [
                'colonnes' => ['Classe', 'Semestre', 'ECUE', 'Professeur actuel', 'Après'],
                'lignes' => [[$classe->name, 'S'.$ids['semestre'], trim(($matiere->code ? $matiere->code.' · ' : '').$matiere->name), $resolution['enseignant_nom'] ?: '—', $teacher->user->name]],
            ],
            avertissements: $avertissements,
            donnees: $ids + ['enseignant_user_id' => (int) $teacher->user_id],
            etat: [
                'enseignant_id' => $actuelId ?: null,
                'source' => $resolution['source'] ?? null,
                'conflit' => (bool) ($resolution['conflit'] ?? false),
                'evaluation_ids' => $evals->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
                'seance_ids' => $seances->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
            ],
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('lmd.planning.edit')) {
            throw new PropositionPerimee('Vous n’avez plus le droit de modifier le planning LMD.');
        }

        $d = $proposition->donnees;
        $classe = ESBTPClasse::find($d['classe_id']);
        $teacher = ESBTPTeacher::with('user')->find($d['teacher_id']);
        if (! $classe || ! $teacher?->user || (int) $teacher->user_id !== (int) $d['enseignant_user_id']) {
            throw new PropositionPerimee('La classe ou l’enseignant a changé depuis la proposition.');
        }

        $avant = $this->enseignants->resoudre($classe, $d['matiere_id'], $d['annee_universitaire_id'], $d['semestre']);
        $etat = [
            'enseignant_id' => ($avant['enseignant_id'] ?? null) ? (int) $avant['enseignant_id'] : null,
            'source' => $avant['source'] ?? null,
            'conflit' => (bool) ($avant['conflit'] ?? false),
            'evaluation_ids' => collect($avant['details']['evaluations'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
            'seance_ids' => collect($avant['details']['seances'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
        ];
        if ($etat !== $proposition->etat) {
            throw new PropositionPerimee('Les affectations ou traces de cette classe ont changé. Relis le professeur avant de valider.');
        }

        try {
            $r = $this->enseignants->confirmer(
                $classe,
                $d['matiere_id'],
                $d['annee_universitaire_id'],
                $d['semestre'],
                $d['enseignant_user_id'],
                (int) $user->id,
            );
        } catch (ValidationException $e) {
            throw new PropositionPerimee(collect($e->errors())->flatten()->first() ?: 'La confirmation du professeur a été refusée.');
        }

        return [
            'message' => sprintf('%s confirmé comme professeur : %d évaluation(s) et %d séance(s) harmonisées.', $teacher->user->name, $r['evaluation_count'], $r['seance_count']),
            'lien' => Route::has('esbtp.lmd.planning.index') ? route('esbtp.lmd.planning.index', [], false) : null,
            'model_type' => ESBTPTeacher::class,
            'model_id' => $teacher->id,
            'details' => $r + ['classe_id' => $classe->id, 'matiere_id' => $d['matiere_id'], 'semestre' => $d['semestre']],
        ];
    }
}
