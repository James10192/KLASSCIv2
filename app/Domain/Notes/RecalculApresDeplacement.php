<?php

namespace App\Domain\Notes;

use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rafraîchit `esbtp_resultats` après un déplacement de notes.
 *
 * POURQUOI CETTE CLASSE EXISTE. Un déplaceur change une coordonnée d'évaluation
 * — classe, matière, année ou période — par un `update()` de query builder.
 * Ce `update()` n'émet aucun événement Eloquent : `ESBTPNoteObserver::saved()`
 * ne tourne pas, `RecomputeStudentResultatJob` n'est jamais lancé, et les deux
 * lignes d'`esbtp_resultats` — celle qu'on quitte et celle qu'on rejoint —
 * gardent la moyenne d'avant.
 *
 * Et la moyenne enregistrée l'emporte sur les notes :
 * `BtsCurrentResultSnapshotService` et `MoyennesDeLApercu` lui donnent la
 * préséance. L'écart ne produit donc ni erreur ni page cassée — seulement un
 * chiffre faux, persisté.
 *
 * LE PIÈGE DU ZÉRO. `NoteCalculationService::studentMatiereAverage([])` rend
 * `0.0`, pas `null`. Et le job ne s'abstient que s'il ne trouve NI note NI
 * ligne : face à une ligne dont les notes sont parties, il écrit 0/20. Relancer
 * le calcul sur la coordonnée qu'on vient de vider y poserait donc un zéro sur
 * une matière que l'élève n'a plus — un zéro que rien ne distingue d'une vraie
 * note (`rien-en-dur.md`, « un montant nul est une valeur »).
 *
 * D'où la règle, et c'est toute la valeur de cette classe :
 *  - la coordonnée REJOINTE est toujours recalculée ;
 *  - la coordonnée QUITTÉE ne l'est que s'il y reste au moins une note ;
 *  - sinon sa ligne est SIGNALÉE, jamais touchée. La garder, la supprimer ou la
 *    reporter ailleurs est une décision d'établissement, pas de code.
 *
 * LA SOURCE D'AUDIT EST `manual`, ET CE N'EST PAS UN DÉTAIL.
 * `esbtp_resultats_recompute_log.source` est un `ENUM('observer', 'command',
 * 'manual')`. Une valeur nouvelle y ferait échouer l'insertion, et
 * `writeAuditLog()` avale cet échec dans un simple avertissement : le recalcul
 * aurait lieu, sa trace disparaîtrait. Un déplacement est décidé par une
 * personne, `manual` est donc juste.
 *
 * POURQUOI PAS UN OBSERVATEUR SUR `ESBTPEvaluation`. Tentant : tous les
 * déplaceurs sauf la fusion enregistrent l'évaluation par Eloquent, et
 * `getOriginal()` rend encore l'ancienne coordonnée dans l'événement
 * `updated`. Mais un observateur décide évaluation par évaluation, au milieu
 * de la transaction, alors que `deplacer()` et `repair()` déplacent un LOT.
 * Deux évaluations d'un même élève quittent le semestre 1 : au passage de la
 * première, il reste une note en S1, donc S1 est mis en file ; après la
 * seconde, S1 est vide. Sur une file `database`, le job part après le commit,
 * ne trouve aucune note face à une ligne existante, et écrit 0/20 — le piège
 * que cette classe existe pour éviter. Sur la file `sync`, la ligne orpheline
 * garde une moyenne intermédiaire au lieu de la sienne. La fusion, elle,
 * passe par `DB::table` et n'émet aucun événement. D'où le relevé explicite,
 * qui décide une seule fois, sur l'état final.
 *
 * COMMENT BRANCHER UN DÉPLACEUR. Les déplaceurs bougent des évaluations
 * entières : {@see releverEvaluations()} AVANT les `update()`, puis
 * {@see apresEvaluations()} APRÈS, dans la même transaction. Le second relit
 * la base plutôt que de se faire dicter la cible. {@see apres()} reste
 * public pour un déplacement qui ne serait pas celui d'une évaluation.
 *
 * LES DÉPLACEURS CONNUS (septembre 2026) — relevé, pas inventaire. Ce décompte
 * a déjà été publié faux trois fois ; les commandes qui le rejouent sont plus
 * bas, et c'est leur sortie qui fait foi, pas ce tableau.
 *
 * | déplaceur                                                   | coordonnée | branché |
 * |-------------------------------------------------------------|------------|---------|
 * | `MergeDuplicateEcue` (sous `force`)                         | matière    | oui     |
 * | `ESBTPEvaluationController::update()`                       | classe, matière, période | oui |
 * | `CLIMaintenanceController::evaluationChangeMatiere()`       | matière    | oui     |
 * | `CLIEvaluationDeplacementController::deplacer()`            | période    | oui     |
 * | `CLIEvaluationPeriodeController::repair()`                  | période    | oui     |
 * | `ESBTPSeanceCoursController::syncHomeworkEvaluation()`      | classe, matière, période, année | non |
 * | `CheckEvaluationsAnnees` (`esbtp:check-evaluations-annees`) | année (depuis nulle) | non |
 *
 * Les deux « non » ont été trouvés en rejouant la recherche, pas dans la
 * liste d'origine. Le premier réécrit par `fill()` les coordonnées du devoir
 * lié à une séance quand la séance change — sans même propager la copie
 * dénormalisée des notes. Le second donne une année à des évaluations qui
 * n'en avaient pas : il n'y a pas de coordonnée quittée, seulement une
 * rejointe, jamais recalculée. Ni l'un ni l'autre n'est sain ; ils ne sont
 * simplement pas dans le périmètre de ce changement.
 *
 * NE SONT PAS des déplaceurs, et pourquoi — pour ne pas les « brancher » par
 * réflexe :
 *  - `evaluations:sync-notes` (`SyncNotesScopeCommand`) et
 *    `esbtp:sync-notes-periodes` réalignent la copie dénormalisée des notes
 *    sur leur évaluation. Le job lit la coordonnée de l'ÉVALUATION : rien
 *    de ce qu'il calcule ne bouge ;
 *  - `ESBTPEvaluationController::updateStatus()` ne déplace rien, mais
 *    ANNULER une évaluation retire ses notes de la moyenne sans la
 *    recalculer. Même symptôme, autre défaut, non traité ici ;
 *  - changer le BARÈME ou le COEFFICIENT d'une évaluation notée (écran
 *    d'édition, `quickUpdate()`) change la moyenne sans la recalculer non
 *    plus : le job lit les deux pour normaliser et pondérer. Même coordonnée
 *    des deux côtés, donc sans piège du zéro — mais pas traité ici non plus.
 *
 * ```bash
 * grep -rnE "update\(\[?\s*'(matiere_id|classe_id|periode|semestre|annee_universitaire_id)'" app/ database/ --include="*.php"
 * grep -rnE "(ESBTPNote|ESBTPEvaluation)::(where|whereIn|query)\(.*update\(" app/ database/ --include="*.php"
 * grep -rnE -e "->(periode|matiere_id|classe_id|annee_universitaire_id)\s*=[^=>]" app/ database/ --include="*.php"
 * grep -rnE '\$\w*eval\w*->(update|fill|forceFill)\(' app/ --include="*.php"
 * ```
 *
 * Le premier motif ne voit pas un `update($variable)` (c'est ainsi que
 * `ESBTPEvaluationController::update()` lui a échappé), ni une affectation
 * suivie d'un `save()` (ainsi `syncHomeworkEvaluation()` et
 * `CheckEvaluationsAnnees`). Les trois suivants les rattrapent, au prix de
 * beaucoup de bruit : chaque ligne se relit.
 */
final class RecalculApresDeplacement
{
    /**
     * @param  iterable<array{etudiant_id:int, avant:array{classe_id:int, matiere_id:int, annee_universitaire_id:int, periode:string}, apres:array{classe_id:int, matiere_id:int, annee_universitaire_id:int, periode:string}}>  $deplacements
     *                                                                                                                                                                                                                                          Un élément par élève et par couple de coordonnées. Les doublons
     *                                                                                                                                                                                                                                          sont tolérés : chaque coordonnée n'est traitée qu'une fois.
     * @param  string  $motif  pour le journal, par exemple « fusion ECUE 12 → 7 »
     * @return array{recalcules:int, orphelins:list<array{etudiant_id:int, classe_id:int, matiere_id:int, annee_universitaire_id:int, periode:string, moyenne:?float, etudiant:?string, classe:?string}>}
     *
     * À appeler DANS la transaction du déplacement, APRÈS ses `update()` : la
     * recherche des notes restantes doit voir l'état déplacé.
     *
     * Sur une file asynchrone, les jobs attendent le commit (`afterCommit()`).
     * Sur la file `sync`, Laravel 9 ignore `afterCommit()` : le job tourne tout
     * de suite, dans la transaction. Il voit donc bien l'état déplacé, et une
     * panne du recalcul annule le déplacement entier au lieu de le laisser à
     * moitié juste. Le refus attendu — matière étrangère au système de la
     * classe — est rattrapé par le job lui-même et n'annule rien.
     */
    public function apres(iterable $deplacements, string $motif, ?int $declenchePar = null): array
    {
        $aRecalculer = [];
        $aExaminer = [];

        foreach ($deplacements as $d) {
            $etudiantId = (int) $d['etudiant_id'];
            $avant = $this->coordonnee($d['avant']);
            $apres = $this->coordonnee($d['apres']);

            $aRecalculer[$this->cle($etudiantId, $apres)] = [$etudiantId, $apres];

            if ($avant !== $apres) {
                $aExaminer[$this->cle($etudiantId, $avant)] = [$etudiantId, $avant];
            }
        }

        $orphelins = [];

        foreach ($aExaminer as $cle => [$etudiantId, $avant]) {
            if (isset($aRecalculer[$cle])) {
                continue;
            }

            if ($this->resteDesNotes($etudiantId, $avant)) {
                $aRecalculer[$cle] = [$etudiantId, $avant];

                continue;
            }

            $ligne = $this->ligneDeResultat($etudiantId, $avant);
            if ($ligne) {
                $orphelins[] = ['etudiant_id' => $etudiantId] + $avant + [
                    'moyenne' => $ligne->moyenne !== null ? (float) $ligne->moyenne : null,
                ];
            }
        }

        foreach ($aRecalculer as [$etudiantId, $c]) {
            RecomputeStudentResultatJob::dispatch(
                etudiantId: $etudiantId,
                classeId: $c['classe_id'],
                matiereId: $c['matiere_id'],
                anneeUniversitaireId: $c['annee_universitaire_id'],
                periode: $c['periode'],
                source: 'manual',
                triggeredBy: $declenchePar,
            )->afterCommit();
        }

        if ($orphelins !== []) {
            $orphelins = $this->nommer($orphelins);

            Log::warning('Deplacement de notes : moyennes laissees sans note, non recalculees', [
                'motif' => $motif,
                'nombre' => count($orphelins),
                'lignes' => $orphelins,
            ]);
        }

        return ['recalcules' => count($aRecalculer), 'orphelins' => $orphelins];
    }

    /**
     * Relève, AVANT le déplacement, qui est noté sur ces évaluations et où
     * elles se trouvent. Après les `update()`, plus rien ne dit d'où venait
     * chaque évaluation : ce relevé est la seule trace de la coordonnée quittée.
     *
     * Écartées, avec les mêmes raisons que le job et l'observateur :
     *  - notes effacées ou archivées, évaluations effacées : le job ne les lit pas ;
     *  - évaluation sans classe, année ou période : pas de coordonnée, donc
     *    rien à recalculer, avant comme après ;
     *  - évaluation ANNULÉE : ses notes ne comptent dans aucune moyenne, la
     *    déplacer ne change donc aucune moyenne. Et la recalculer serait
     *    dangereux : sur une coordonnée rejointe qui n'aurait qu'une ligne
     *    ancienne et aucune note valide, le job écrirait 0/20.
     *
     * `DB::table` et non le modèle : ce relevé doit lire exactement ce que
     * voit le job, sans portée globale supplémentaire.
     *
     * @param  iterable<int>  $evaluationIds
     * @return list<array{evaluation_id:int, etudiant_id:int, avant:array{classe_id:int, matiere_id:int, annee_universitaire_id:int, periode:string}}>
     */
    public function releverEvaluations(iterable $evaluationIds): array
    {
        $ids = array_values(array_unique(array_map('intval', is_array($evaluationIds) ? $evaluationIds : iterator_to_array($evaluationIds, false))));

        if ($ids === []) {
            return [];
        }

        return DB::table('esbtp_notes as n')
            ->join('esbtp_evaluations as e', 'e.id', '=', 'n.evaluation_id')
            ->whereIn('e.id', $ids)
            ->whereNull('n.deleted_at')
            ->whereNull('n.archived_at')
            ->whereNull('e.deleted_at')
            ->where('e.status', '!=', 'cancelled')
            ->whereNotNull('e.classe_id')
            ->whereNotNull('e.matiere_id')
            ->whereNotNull('e.annee_universitaire_id')
            ->whereNotNull('e.periode')
            ->distinct()
            ->get(['e.id as evaluation_id', 'n.etudiant_id', 'e.classe_id', 'e.matiere_id', 'e.annee_universitaire_id', 'e.periode'])
            ->map(fn ($r) => [
                'evaluation_id' => (int) $r->evaluation_id,
                'etudiant_id' => (int) $r->etudiant_id,
                'avant' => $this->coordonnee((array) $r),
            ])
            ->all();
    }

    /**
     * Second temps de {@see releverEvaluations()} : relit où se trouve chaque
     * évaluation relevée APRÈS les `update()`, et transmet à {@see apres()}.
     *
     * Relire plutôt que se faire dicter la cible par l'appelant : c'est ce que
     * la base porte réellement qui compte, pas ce qu'on croyait y écrire
     * (l'écran d'édition, par exemple, ne change la classe que sous permission).
     *
     * Une évaluation qui n'a pas bougé n'est pas un déplacement : elle est
     * ignorée, et ne coûte aucun recalcul.
     *
     * @param  list<array{evaluation_id:int, etudiant_id:int, avant:array}>  $releve
     * @return array{recalcules:int, orphelins:list<array{etudiant_id:int, classe_id:int, matiere_id:int, annee_universitaire_id:int, periode:string, moyenne:?float, etudiant:?string, classe:?string}>}
     */
    public function apresEvaluations(array $releve, string $motif, ?int $declenchePar = null): array
    {
        if ($releve === []) {
            return ['recalcules' => 0, 'orphelins' => []];
        }

        $maintenant = DB::table('esbtp_evaluations')
            ->whereIn('id', array_unique(array_column($releve, 'evaluation_id')))
            ->whereNotNull('classe_id')
            ->whereNotNull('matiere_id')
            ->whereNotNull('annee_universitaire_id')
            ->whereNotNull('periode')
            ->get(['id', 'classe_id', 'matiere_id', 'annee_universitaire_id', 'periode'])
            ->keyBy('id');

        $deplacements = [];

        foreach ($releve as $ligne) {
            $evaluation = $maintenant->get($ligne['evaluation_id']);
            if (! $evaluation) {
                continue;
            }

            $apres = $this->coordonnee((array) $evaluation);
            if ($apres === $this->coordonnee($ligne['avant'])) {
                continue;
            }

            $deplacements[] = ['etudiant_id' => $ligne['etudiant_id'], 'avant' => $ligne['avant'], 'apres' => $apres];
        }

        return $this->apres($deplacements, $motif, $declenchePar);
    }

    /**
     * Une ligne orpheline est une décision à prendre par une personne : elle
     * doit pouvoir la lire sans aller traduire des identifiants. Deux requêtes,
     * seulement quand il y a des orphelins. `DB::table` : un élève ou une classe
     * effacés en douceur gardent leur nom.
     *
     * @param  list<array{etudiant_id:int, classe_id:int}>  $orphelins
     * @return list<array>
     */
    private function nommer(array $orphelins): array
    {
        $eleves = DB::table('esbtp_etudiants')
            ->whereIn('id', array_unique(array_column($orphelins, 'etudiant_id')))
            ->get(['id', 'nom', 'prenoms'])
            ->keyBy('id');
        $classes = DB::table('esbtp_classes')
            ->whereIn('id', array_unique(array_column($orphelins, 'classe_id')))
            ->pluck('name', 'id');

        return array_map(function (array $o) use ($eleves, $classes) {
            $eleve = $eleves->get($o['etudiant_id']);

            return $o + [
                'etudiant' => $eleve ? trim($eleve->nom.' '.$eleve->prenoms) : null,
                'classe' => $classes->get($o['classe_id']),
            ];
        }, $orphelins);
    }

    /**
     * Même filtre que le job : c'est ce qu'il lirait, donc c'est ce qui décide
     * s'il écrirait une moyenne ou un zéro.
     */
    private function resteDesNotes(int $etudiantId, array $c): bool
    {
        return ESBTPNote::query()
            ->where('etudiant_id', $etudiantId)
            ->whereHas('evaluation', fn ($q) => $q
                ->where('classe_id', $c['classe_id'])
                ->where('matiere_id', $c['matiere_id'])
                ->where('annee_universitaire_id', $c['annee_universitaire_id'])
                ->whereIn('periode', ESBTPEvaluation::aliasDePeriode($c['periode']))
                ->where('status', '!=', 'cancelled'))
            ->exists();
    }

    private function ligneDeResultat(int $etudiantId, array $c): ?ESBTPResultat
    {
        return ESBTPResultat::query()
            ->where('etudiant_id', $etudiantId)
            ->where('classe_id', $c['classe_id'])
            ->where('matiere_id', $c['matiere_id'])
            ->where('annee_universitaire_id', $c['annee_universitaire_id'])
            ->where('periode', $c['periode'])
            ->first();
    }

    /**
     * @return array{classe_id:int, matiere_id:int, annee_universitaire_id:int, periode:string}
     */
    private function coordonnee(array $c): array
    {
        return [
            'classe_id' => (int) $c['classe_id'],
            'matiere_id' => (int) $c['matiere_id'],
            'annee_universitaire_id' => (int) $c['annee_universitaire_id'],
            'periode' => RecomputeStudentResultatJob::periodeNormalisee((string) $c['periode']),
        ];
    }

    private function cle(int $etudiantId, array $c): string
    {
        return implode(':', [$etudiantId, $c['classe_id'], $c['matiere_id'], $c['annee_universitaire_id'], $c['periode']]);
    }
}
