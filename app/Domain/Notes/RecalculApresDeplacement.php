<?php

namespace App\Domain\Notes;

use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
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
 * LES DÉPLACEURS CONNUS (septembre 2026) — relevé, pas inventaire. Ce décompte
 * a déjà été publié faux deux fois ; la commande qui le rejoue est plus bas.
 *
 * | déplaceur                                                   | coordonnée | branché |
 * |-------------------------------------------------------------|------------|---------|
 * | `MergeDuplicateEcue` (sous `force`)                         | matière    | oui     |
 * | `ESBTPEvaluationController::update()`                       | classe, matière, période | non |
 * | `CLIMaintenanceController::evaluationChangeMatiere()`       | matière    | non     |
 * | `CLIEvaluationDeplacementController::deplacer()`            | période    | non     |
 * | `CLIEvaluationPeriodeController`                            | période    | non     |
 *
 * Les quatre « non » ont exactement le défaut décrit ici. Ils ne sont pas
 * branchés dans ce changement parce qu'ils touchent l'écran d'édition des
 * évaluations, sur huit instances, et demandent leur propre revue — pas parce
 * qu'ils seraient sains.
 *
 * ```bash
 * grep -rnE "update\(\[?\s*'(matiere_id|classe_id|periode|semestre)'" app/ database/ --include="*.php"
 * grep -rn "ESBTPNote::where('evaluation_id'" app/ --include="*.php"
 * ```
 *
 * Le motif ne voit pas un `update($variable)` (c'est ainsi que
 * `ESBTPEvaluationController::update()` lui échappe) : relisez les résultats
 * du second, pas seulement ceux du premier.
 */
final class RecalculApresDeplacement
{
    /**
     * @param  iterable<array{etudiant_id:int, avant:array{classe_id:int, matiere_id:int, annee_universitaire_id:int, periode:string}, apres:array{classe_id:int, matiere_id:int, annee_universitaire_id:int, periode:string}}>  $deplacements
     *                                                                                                                                                                                                                                          Un élément par élève et par couple de coordonnées. Les doublons
     *                                                                                                                                                                                                                                          sont tolérés : chaque coordonnée n'est traitée qu'une fois.
     * @param  string  $motif  pour le journal, par exemple « fusion ECUE 12 → 7 »
     * @return array{recalcules:int, orphelins:list<array{etudiant_id:int, classe_id:int, matiere_id:int, annee_universitaire_id:int, periode:string, moyenne:?float}>}
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
            Log::warning('Deplacement de notes : moyennes laissees sans note, non recalculees', [
                'motif' => $motif,
                'nombre' => count($orphelins),
                'lignes' => $orphelins,
            ]);
        }

        return ['recalcules' => count($aRecalculer), 'orphelins' => $orphelins];
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
                ->where('periode', $c['periode'])
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
