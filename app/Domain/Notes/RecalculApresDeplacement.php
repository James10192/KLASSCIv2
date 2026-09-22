<?php

namespace App\Domain\Notes;

use App\Jobs\RecomputeStudentResultatJob;
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
 * ne tourne pas, `RecomputeStudentResultatJob` n'est jamais lancé, et les
 * lignes d'`esbtp_resultats` gardent la moyenne d'avant. Or le bulletin BTS
 * donne la préséance à la moyenne enregistrée sur les notes
 * (`BtsCurrentResultSnapshotService`, `MoyennesDeLApercu`) : l'écart ne produit
 * ni erreur ni page cassée, seulement un chiffre faux, persisté.
 *
 * DEUX TEMPS, POUR QUE LE DÉPLACEUR N'AIT RIEN À SAVOIR. {@see releverAvant()}
 * avant les `update()`, {@see apres()} après. La seconde relit elle-même les
 * coordonnées actuelles des évaluations relevées : le déplaceur n'a pas à dire
 * laquelle des quatre coordonnées il a changée, ni à construire la nouvelle.
 *
 * LE PIÈGE DU ZÉRO. `NoteCalculationService::studentMatiereAverage([])` rend
 * `0.0`. Une coordonnée n'est donc recalculée que s'il y a au moins une note —
 * des DEUX côtés : la quittée peut avoir été vidée, la rejointe peut n'en
 * recevoir aucune (évaluation annulée). Le job porte la même garde depuis
 * septembre 2026 ; celle-ci décide en plus de ce qu'on RAPPORTE.
 *
 * UNE LIGNE SANS NOTE : DEUX CONDUITES, DEUX MÉTHODES.
 *  - {@see apres()} la SIGNALE, jamais touchée. C'est la conduite de la
 *    rebascule CLI, qui ne laisse que des lignes incohérentes avec leur
 *    classe : tous les lecteurs filtrés les écartent déjà.
 *  - {@see apresEnRetirantLesLignesVidees()} MET DE CÔTÉ (suppression réversible,
 *    tracée par l'audit) une ligne que le déplacement a VIDÉE — une coordonnée
 *    qui portait des notes juste avant et n'en porte plus. Sa moyenne portait
 *    sur ces notes-là, saisie manuelle comprise ; cohérente, elle resterait lue
 *    et les notes compteraient deux fois. Décision de l'établissement
 *    (septembre 2026) pour les déplacements de période, de classe ou de matière
 *    entre coordonnées cohérentes. Une ligne sans note du côté REJOINT n'a
 *    rien perdu : elle est seulement signalée.
 *
 * Pourquoi la rebascule peut se contenter de signaler : une ligne laissée sur
 * la coordonnée quittée n'est sans danger que si elle est INCOHÉRENTE avec sa
 * classe (`CoherenceSystemeAcademique::resultatsRetenus()` et la génération du
 * bulletin BTS l'écartent). La rebascule refuse donc tout déplacement entre
 * deux matières déjà cohérentes.
 *
 * LA SOURCE D'AUDIT EST `manual`. `esbtp_resultats_recompute_log.source` est un
 * `ENUM('observer', 'command', 'manual')` : une autre valeur y ferait échouer
 * l'insertion, et `writeAuditLog()` avale l'échec. Le recalcul aurait lieu, sa
 * trace disparaîtrait.
 *
 * LES DÉPLACEURS CONNUS (septembre 2026) — relevé, pas inventaire. Ce décompte
 * a déjà été publié faux deux fois ; la commande qui le rejoue est plus bas.
 *
 * | déplaceur                                             | coordonnée               | branché |
 * |-------------------------------------------------------|--------------------------|---------|
 * | `CLIMaintenanceController::evaluationChangeMatiere()` | matière                  | signale |
 * | `ESBTPEvaluationController::update()`                 | classe, matière, période | retire  |
 * | `CLIEvaluationDeplacementController::deplacer()`      | période                  | retire  |
 * | `CLIEvaluationPeriodeController::repair()`            | période                  | retire  |
 * | `MergeDuplicateEcue` (sous `force`)                   | matière                  | exclu   |
 *
 * La fusion d'ECUE est EXCLUE, et ce n'est pas un oubli : elle ne déplace que
 * des ECUE, dont la moyenne se relit sur les notes (`LMDBulletinService`, par
 * `esbtp_notes.matiere_id`, que la fusion déplace déjà). Aucun écran LMD ne lit
 * `esbtp_resultats`, et le repli sans bulletin du certificat de scolarité
 * compterait deux fois les notes absorbées si l'on recalculait l'élément
 * conservé en laissant la ligne de l'absorbé — une ECUE est cohérente dans sa
 * classe LMD, le filtre ne l'écarte pas. Voir `MergeDuplicateEcue`.
 *
 * ```bash
 * grep -rnE "update\(\[?\s*'(matiere_id|classe_id|periode|semestre)'" app/ database/ --include="*.php"
 * grep -rn "ESBTPNote::where('evaluation_id'" app/ --include="*.php"
 * ```
 *
 * Le premier motif ne voit pas un `update($variable)` (c'est ainsi que
 * `ESBTPEvaluationController::update()` lui échappe) : relisez les résultats du
 * second, pas seulement ceux du premier.
 */
final class RecalculApresDeplacement
{
    /**
     * Qui a une note sur ces évaluations, et à quelle coordonnée — AVANT le
     * déplacement : après, rien ne dit plus d'où venait chaque évaluation.
     *
     * Mêmes exclusions que le job : notes effacées ou archivées, évaluations
     * effacées ou ANNULÉES. Ce dernier point n'est pas cosmétique : les notes
     * d'une évaluation annulée ne comptent nulle part, donc la déplacer ne vide
     * rien — la relever ferait prendre pour « vidée » une coordonnée dont la
     * ligne ne leur devait rien (une saisie manuelle, par exemple), et
     * {@see apresEnRetirantLesLignesVidees()} la mettrait de côté. Une évaluation
     * sans classe, année ou période n'est pas recalculable non plus.
     *
     * @param  list<int>  $evaluationIds
     * @return list<array{etudiant_id:int, evaluation_id:int, avant:array}>
     */
    public function releverAvant(array $evaluationIds): array
    {
        return DB::table('esbtp_notes as n')
            ->join('esbtp_evaluations as e', 'e.id', '=', 'n.evaluation_id')
            ->whereIn('e.id', $evaluationIds)
            ->whereNull('n.deleted_at')
            ->whereNull('n.archived_at')
            ->whereNull('e.deleted_at')
            ->where('e.status', '!=', 'cancelled')
            ->whereNotNull('e.classe_id')
            ->whereNotNull('e.matiere_id')
            ->whereNotNull('e.annee_universitaire_id')
            ->whereNotNull('e.periode')
            ->distinct()
            ->get(['n.etudiant_id', 'e.id as evaluation_id', 'e.classe_id', 'e.matiere_id', 'e.annee_universitaire_id', 'e.periode'])
            ->map(fn ($r) => [
                'etudiant_id' => (int) $r->etudiant_id,
                'evaluation_id' => (int) $r->evaluation_id,
                'avant' => $this->coordonnee((array) $r),
            ])
            ->all();
    }

    /**
     * À appeler DANS la transaction du déplacement, APRÈS ses `update()`.
     *
     * Sur une file asynchrone, les jobs attendent le commit (`afterCommit()`) :
     * `recalculs_lances` compte alors des recalculs PROGRAMMÉS, pas faits. Sur la
     * file `sync`, Laravel 9 ignore `afterCommit()` : le job tourne tout de
     * suite, dans la transaction, et une panne du recalcul annule le déplacement
     * entier. Le refus attendu — matière étrangère au système de la classe — est
     * rattrapé par le job lui-même et n'annule rien.
     *
     * @param  list<array{etudiant_id:int, evaluation_id:int, avant:array}>  $releve  rendu par releverAvant()
     * @param  string  $motif  pour le journal, par exemple « rebascule évaluation 4117 »
     * @return array{recalculs_lances:int, lignes_sans_note:list<array>}
     */
    public function apres(array $releve, string $motif, ?int $declenchePar = null): array
    {
        return $this->traiter($releve, $motif, $declenchePar, false);
    }

    /**
     * Comme {@see apres()}, mais une ligne que le déplacement a vidée est mise de
     * côté au lieu d'être signalée. Voir l'en-tête pour le pourquoi.
     *
     * @return array{recalculs_lances:int, lignes_sans_note:list<array>, lignes_retirees:list<array>}
     */
    public function apresEnRetirantLesLignesVidees(array $releve, string $motif, ?int $declenchePar = null): array
    {
        return $this->traiter($releve, $motif, $declenchePar, true);
    }

    private function traiter(array $releve, string $motif, ?int $declenchePar, bool $retirerLesVidees): array
    {
        $maintenant = DB::table('esbtp_evaluations')
            ->whereIn('id', array_unique(array_column($releve, 'evaluation_id')))
            ->get(['id', 'classe_id', 'matiere_id', 'annee_universitaire_id', 'periode'])
            ->keyBy('id');

        // [étudiant, coordonnée, vidée ?] — « vidée » : elle portait les notes
        // relevées avant le déplacement. Une coordonnée qui est à la fois quittée
        // et rejointe (deux évaluations échangées) n'est pas vidée si des notes
        // y restent : le test aDesNotes() tranche avant.
        $coordonnees = [];
        foreach ($releve as $r) {
            $coordonnees[$this->cle($r['etudiant_id'], $r['avant'])] = [$r['etudiant_id'], $r['avant'], true];
        }
        foreach ($releve as $r) {
            $apres = $maintenant->get($r['evaluation_id']);
            $c = $apres ? $this->coordonnee((array) $apres) : null;
            if ($c) {
                $coordonnees[$this->cle($r['etudiant_id'], $c)] ??= [$r['etudiant_id'], $c, false];
            }
        }

        $lances = 0;
        $sansNote = [];
        $retirees = [];

        foreach ($coordonnees as [$etudiantId, $c, $videe]) {
            if ($this->aDesNotes($etudiantId, $c)) {
                $this->lancer($etudiantId, $c, $declenchePar);
                $lances++;
            } elseif ($ligne = $this->ligneDeResultat($etudiantId, $c)) {
                $trace = ['etudiant_id' => $etudiantId] + $c + [
                    'moyenne' => $ligne->moyenne !== null ? (float) $ligne->moyenne : null,
                ];
                if ($retirerLesVidees && $videe) {
                    $ligne->delete();
                    $retirees[] = $trace;
                } else {
                    $sansNote[] = $trace;
                }
            }
        }

        if ($retirees !== []) {
            Log::warning('Deplacement de notes : moyennes videes par le deplacement, mises de cote', [
                'motif' => $motif,
                'nombre' => count($retirees),
                'lignes' => $retirees,
            ]);
        }

        if ($sansNote !== []) {
            Log::warning('Deplacement de notes : moyennes laissees sans note, non recalculees', [
                'motif' => $motif,
                'nombre' => count($sansNote),
                'lignes' => $sansNote,
            ]);
        }

        $rapport = ['recalculs_lances' => $lances, 'lignes_sans_note' => $sansNote];

        return $retirerLesVidees ? $rapport + ['lignes_retirees' => $retirees] : $rapport;
    }

    /**
     * Même filtre que le job : c'est ce qu'il lirait, donc c'est ce qui décide
     * s'il écrirait une moyenne ou un zéro.
     */
    private function aDesNotes(int $etudiantId, array $c): bool
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

    private function lancer(int $etudiantId, array $c, ?int $declenchePar): void
    {
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
     * La période passe par la table de correspondance du job : c'est la ligne
     * qu'IL écrit qu'il faut retrouver.
     *
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
