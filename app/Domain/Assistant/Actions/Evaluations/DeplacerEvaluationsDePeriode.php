<?php

namespace App\Domain\Assistant\Actions\Evaluations;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Designations;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Notes\DeplacementDePeriode;
use App\Domain\Notes\RecalculApresDeplacement;
use App\Models\ESBTPEvaluation;
use Illuminate\Support\Facades\Log;

/**
 * Ranger des évaluations sur l'autre semestre, sur décision de l'école : rien
 * dans la donnée ne permet de deviner la bonne période. DeplacementDePeriode,
 * le même chemin que `POST /api/cli/evaluations/deplacer-periode` : déplace,
 * réaligne les notes, puis recalcule les moyennes des deux semestres.
 */
class DeplacerEvaluationsDePeriode extends ActionAgent
{
    use Designations;

    public function __construct(private DeplacementDePeriode $deplacement)
    {
    }

    public function cle(): string
    {
        return 'deplacement_periode';
    }

    public function libelle(): string
    {
        return 'Préparation du changement de semestre…';
    }

    public function description(): string
    {
        return "PROPOSE de ranger des évaluations sur un autre semestre (S1 ↔ S2), notes comprises, puis recalcule les moyennes des deux semestres. "
            . "Évaluations par leurs identifiants (search_evaluations), jamais devinées par leur date : c'est l'école qui dit lesquelles.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'evaluation_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                'periode' => ['type' => 'string', 'description' => 'Semestre de destination : S1 ou S2.'],
            ],
            'required' => ['evaluation_ids', 'periode'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Changement de semestre';
        if (! $user->can('evaluations.edit')) {
            return $this->seulManque($titre, "Cet utilisateur n'a pas le droit de modifier des évaluations.");
        }
        $cible = $this->designerSemestre($args['periode'] ?? '');
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($args['evaluation_ids'] ?? [])))));
        $manques = array_values(array_filter([
            $cible === null ? 'Vers quel semestre (S1 ou S2) ?' : null,
            $ids === [] ? 'Quelles évaluations (identifiants donnés par search_evaluations) ?' : null,
            count($ids) > DeplacementDePeriode::LOT_MAX ? 'Trop d\'évaluations en une fois (' . DeplacementDePeriode::LOT_MAX . ' au plus).' : null,
        ]));
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        [$aDeplacer, $deja, $introuvables] = $this->deplacement->apercu($ids, $cible);
        if ($introuvables !== []) {
            return $this->seulManque($titre, 'Évaluation(s) introuvable(s) : #' . implode(', #', $introuvables) . '. Vérifie avec search_evaluations.');
        }
        if ($aDeplacer === []) {
            return $this->seulManque($titre, 'Rien à déplacer : ces évaluations sont déjà en ' . $this->libelleSemestre($cible) . '.');
        }
        $notes = array_sum(array_column($aDeplacer, 'nb_notes'));

        return new Proposition(
            titre: 'Ranger ' . count($aDeplacer) . ' évaluation(s) en ' . $this->libelleSemestre($cible),
            resume: count($aDeplacer) . " évaluation(s) et {$notes} note(s) passent en " . $this->libelleSemestre($cible) . ' ; les moyennes des deux semestres sont recalculées.',
            tableau: [
                'colonnes' => ['Évaluation', 'Classe', 'Matière', 'Date', 'Notes', 'Semestre'],
                'lignes' => array_map(fn ($l) => [
                    (string) $l['titre'], (string) $l['classe'], (string) $l['matiere'], (string) $l['date'], (string) $l['nb_notes'],
                    $this->libelleSemestre($l['periode_actuelle']) . ' → ' . $this->libelleSemestre($cible),
                ], $aDeplacer),
            ],
            avertissements: array_values(array_filter([
                $deja !== [] ? count($deja) . ' évaluation(s) sont déjà en ' . $this->libelleSemestre($cible) . ' : laissées telles quelles.' : null,
                'Un bulletin déjà généré garde ses moyennes tant qu\'il n\'est pas régénéré.',
            ])),
            donnees: ['evaluation_ids' => array_column($aDeplacer, 'evaluation_id'), 'periode' => $cible],
            etat: ['a_deplacer' => $aDeplacer],
            risque: $notes > 0 ? 'eleve' : 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('evaluations.edit')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de modifier des évaluations.");
        }
        $cible = $proposition->donnees['periode'];
        [$aDeplacer] = $this->deplacement->apercu($proposition->donnees['evaluation_ids'], $cible);
        if ($aDeplacer !== $proposition->etat['a_deplacer']) {
            throw new PropositionPerimee('Ces évaluations ont changé depuis la proposition.');
        }

        ['traitees' => $traitees, 'recalcul' => $recalcul] = $this->deplacement->appliquer($aDeplacer, $cible, (int) $user->id);
        Log::warning('assistant: evaluations deplacees de semestre sur decision humaine', [
            'periode_cible' => $cible, 'evaluations' => array_column($traitees, 'evaluation_id'), 'user_id' => $user->id,
        ]);

        return [
            'message' => count($traitees) . ' évaluation(s) rangée(s) en ' . $this->libelleSemestre($cible) . '.' . RecalculApresDeplacement::motDeLaFin($recalcul),
            'lien' => route('esbtp.evaluations.index', [], false),
            'model_type' => ESBTPEvaluation::class,
            'model_id' => (int) ($traitees[0]['evaluation_id'] ?? 0) ?: null,
            'details' => ['recalculs_tentes' => $recalcul['recalculs_tentes'], 'echecs' => $recalcul['echecs']],
        ];
    }
}
