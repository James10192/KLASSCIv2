<?php

namespace App\Domain\Assistant\Actions\Evaluations;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Designations;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Notes\RebasculeDeMatiere;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Services\LMD\CodeDeMaquette;
use Illuminate\Support\Facades\Log;

/**
 * Rebasculer une évaluation posée sur une matière du mauvais système (une ECUE
 * LMD évaluée dans une classe BTS, ou l'inverse) vers la bonne matière, notes
 * comprises : RebasculeDeMatiere, le même chemin que
 * `POST /api/cli/evaluations/{id}/matiere`. Le mouvement n'est permis que s'il
 * RÉTABLIT la cohérence ; les moyennes des deux matières sont recalculées.
 */
class ChangerMatiereEvaluation extends ActionAgent
{
    use Designations;

    public function __construct(private RebasculeDeMatiere $rebascule)
    {
    }

    public function cle(): string
    {
        return 'changement_matiere_evaluation';
    }

    public function libelle(): string
    {
        return 'Préparation du changement de matière…';
    }

    public function description(): string
    {
        return "PROPOSE de rattacher UNE évaluation (et ses notes) à une autre matière, quand elle a été posée sur une matière du mauvais système (ECUE LMD dans une classe BTS, ou l'inverse). "
            . "Évaluation par son identifiant (search_evaluations), matière cible par identifiant ou code exact. Refusé si le mouvement ne rétablit pas la cohérence.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'evaluation_id' => ['type' => 'integer'],
                'matiere_id' => ['type' => 'integer', 'description' => 'Matière cible, si son identifiant est connu.'],
                'matiere' => ['type' => 'string', 'description' => 'Code ou intitulé exact de la matière cible.'],
            ],
            'required' => ['evaluation_id'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Changement de matière';
        if (! $user->can('evaluations.edit')) {
            return $this->seulManque($titre, "Cet utilisateur n'a pas le droit de modifier des évaluations.");
        }
        $evaluation = ESBTPEvaluation::with(['classe', 'matiere'])->find((int) ($args['evaluation_id'] ?? 0));
        if (! $evaluation) {
            return $this->seulManque($titre, 'Évaluation introuvable : retrouve-la avec search_evaluations et donne son id.');
        }
        [$cible, $manque] = $this->designerMatiere($args['matiere_id'] ?? 0, $args['matiere'] ?? '');
        if (! $cible) {
            return $this->seulManque($titre, $manque);
        }
        if ((int) $cible->id === (int) $evaluation->matiere_id) {
            return $this->seulManque($titre, "L'évaluation est déjà sur {$cible->name} : rien à changer.");
        }
        if ($refus = $this->rebascule->refus($evaluation, $cible)) {
            return $this->seulManque($titre, $refus . ' Ce changement ne sert qu\'à rétablir la cohérence ; pour une autre erreur de matière, passez par l\'écran de l\'évaluation.');
        }
        $etat = $this->etat($evaluation);

        return new Proposition(
            titre: 'Rattacher « ' . ($evaluation->titre ?: 'Évaluation') . ' » à ' . $cible->name,
            resume: sprintf('%s (%s) : %s → %s, %d note(s) suivent ; les moyennes des deux matières sont recalculées.',
                $evaluation->titre ?: 'Évaluation', $evaluation->classe?->name, $evaluation->matiere?->name, $cible->name, $etat['notes']),
            tableau: [
                'colonnes' => ['Évaluation', 'Classe', 'Matière actuelle', 'Après', 'Notes'],
                'lignes' => [[(string) $evaluation->titre, (string) $evaluation->classe?->name,
                    $this->matiereAffichee($evaluation->matiere), $this->matiereAffichee($cible), (string) $etat['notes']]],
            ],
            avertissements: ['Un bulletin déjà généré garde ses moyennes tant qu\'il n\'est pas régénéré.'],
            donnees: ['evaluation_id' => (int) $evaluation->id, 'matiere_id' => (int) $cible->id],
            etat: $etat,
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('evaluations.edit')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de modifier des évaluations.");
        }
        $evaluation = ESBTPEvaluation::with(['classe', 'matiere'])->find($proposition->donnees['evaluation_id']);
        $cible = ESBTPMatiere::find($proposition->donnees['matiere_id']);
        if (! $evaluation || ! $cible || $this->etat($evaluation) !== $proposition->etat || $this->rebascule->refus($evaluation, $cible)) {
            throw new PropositionPerimee('Cette évaluation a changé depuis la proposition.');
        }

        $r = $this->rebascule->appliquer($evaluation, $cible, (int) $user->id);
        Log::warning('assistant: evaluation rebasculee', [
            'evaluation_id' => $evaluation->id, 'avant' => $r['avant'], 'apres' => ['matiere_id' => $cible->id, 'matiere' => $cible->name],
            'notes_deplacees' => $r['notes'], 'user_id' => $user->id,
        ]);

        return [
            'message' => "Évaluation rattachée à {$cible->name} ({$r['notes']} note(s) suivie(s), {$r['recalcul']['recalculs_tentes']} moyenne(s) recalculée(s)).",
            'lien' => route('esbtp.evaluations.index', [], false),
            'model_type' => ESBTPEvaluation::class,
            'model_id' => (int) $evaluation->id,
            'details' => ['notes' => $r['notes'], 'orphelins' => count($r['recalcul']['orphelins'])],
        ];
    }

    /** @return array{matiere_id: int, classe_id: int, periode: string, notes: int} */
    private function etat(ESBTPEvaluation $evaluation): array
    {
        return [
            'matiere_id' => (int) $evaluation->matiere_id,
            'classe_id' => (int) $evaluation->classe_id,
            'periode' => (string) $evaluation->periode,
            'notes' => ESBTPNote::where('evaluation_id', $evaluation->id)->count(),
        ];
    }

    private function matiereAffichee(?ESBTPMatiere $m): string
    {
        return $m ? trim(CodeDeMaquette::affiche((string) $m->code) . ' ' . $m->name) . ($m->unite_enseignement_id ? ' (ECUE LMD)' : ' (BTS)') : '—';
    }
}
