<?php

namespace App\Domain\Notes;

use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use Illuminate\Support\Facades\Log;

/**
 * Recalcule les agregats d'`esbtp_resultats` apres qu'une evaluation a change
 * de classe, de matiere ou de periode.
 *
 * ## Pourquoi cette classe existe
 *
 * Les deux endroits qui deplacent une evaluation propagent la colonne
 * denormalisee `esbtp_notes.matiere_id` par un `update()` de **query builder** :
 *
 *     ESBTPNote::where('evaluation_id', $id)->update(['matiere_id' => $cible->id]);
 *
 * Un `update()` de query builder **n'emet aucun evenement Eloquent**. Donc
 * `ESBTPNoteObserver::saved()` ne tourne pas, `RecomputeStudentResultatJob`
 * n'est jamais dispatche, et les deux coordonnees restent figees sur leur
 * ancienne valeur : l'ancienne matiere garde une moyenne qu'aucune note ne
 * justifie plus, la nouvelle garde une moyenne qui ignore les notes arrivees.
 *
 * Ce n'est pas un defaut d'affichage. `BtsCurrentResultSnapshotService` et
 * `MoyennesDeLApercu` donnent la **preseance a la ligne enregistree** : quand
 * `esbtp_resultats` porte une valeur, elle ecrase celle calculee depuis les
 * notes. L'agregat perime gagne donc sur les notes, en silence.
 *
 * Mesure du 2026-09-20 sur esbtp-abidjan, apres un deplacement : eleve 149,
 * matiere 14, semestre2 — l'agregat disait **15**, les cinq notes (10, 20, 10,
 * 18, 20) disent **15,6**. Le 18 etait en base, au bon endroit, et avale.
 *
 * ## Le piege du zero, et pourquoi l'ancienne coordonnee est traitee a part
 *
 * `NoteCalculationService::studentMatiereAverage([])` rend **0.0**, pas `null`.
 * Rejouer le recalcul sur une coordonnee que le deplacement a videe de toutes
 * ses notes n'effacerait donc pas la ligne : il y **ecrirait un 0/20**, sur une
 * matiere que l'eleve n'a plus. C'est strictement pire que la valeur perimee.
 *
 * D'ou la regle : l'ancienne coordonnee n'est recalculee que s'il y reste au
 * moins une note. Sinon la ligne est **signalee, jamais touchee** — le sort
 * d'un agregat orphelin est une decision d'ecole, pas de code
 * (`.claude/rules/rien-en-dur.md`, « le cas particulier du zero »). Le menage
 * se fait sciemment, avec `evaluations:sync-notes --clean-resultats` borne.
 *
 * ## Synchrone, et pas sur la file
 *
 * Le job est execute **sur place** (`dispatchSync`), pas dispatche. Deux
 * raisons : l'operateur qui vient de deplacer une evaluation doit voir l'ecran
 * juste tout de suite, et surtout rien ne prouve qu'un worker tourne sur les
 * instances mutualisees — `config/queue.php` vaut `database` par defaut et
 * `app/Console/Kernel.php` ne planifie aucun `queue:work`. Dispatcher aurait
 * donne un correctif qui a l'air pose et ne s'execute jamais.
 *
 * Le volume est borne par construction : les eleves notes sur **une** seule
 * evaluation, au plus deux coordonnees chacun.
 */
final class RecalculApresDeplacement
{
    /** Valeur ecrite dans `esbtp_resultats_recompute_log.source`. */
    public const SOURCE = 'deplacement';

    /**
     * @param  array{classe_id?:int|null, matiere_id?:int|null, periode?:string|null, annee_universitaire_id?:int|null}  $avant
     *                                                                                                                          Coordonnees de l'evaluation AVANT le deplacement.
     * @return array{recalcules:int, orphelins:array<int,array<string,mixed>>, echecs:int}
     */
    public static function pour(ESBTPEvaluation $evaluation, array $avant, ?int $declencheur = null): array
    {
        $apres = [
            'classe_id' => $evaluation->classe_id,
            'matiere_id' => $evaluation->matiere_id,
            'periode' => $evaluation->periode,
            'annee_universitaire_id' => $evaluation->annee_universitaire_id,
        ];

        $bilan = ['recalcules' => 0, 'orphelins' => [], 'echecs' => 0];

        if (self::memeCoordonnee($avant, $apres)) {
            return $bilan;
        }

        $etudiantIds = ESBTPNote::where('evaluation_id', $evaluation->id)
            ->distinct()
            ->pluck('etudiant_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($etudiantIds === []) {
            return $bilan;
        }

        foreach ($etudiantIds as $etudiantId) {
            // Nouvelle coordonnee : les notes viennent d'y arriver, donc le
            // calcul porte sur au moins une note et ne peut pas rendre un faux 0.
            if (self::executer($etudiantId, $apres, $declencheur, $bilan)) {
                $bilan['recalcules']++;
            }

            // Ancienne coordonnee : recalcul seulement s'il y reste des notes.
            if (! self::coordonneeComplete($avant)) {
                continue;
            }

            if (self::porteEncoreDesNotes($etudiantId, $avant)) {
                if (self::executer($etudiantId, $avant, $declencheur, $bilan)) {
                    $bilan['recalcules']++;
                }

                continue;
            }

            if ($orphelin = self::agregatOrphelin($etudiantId, $avant)) {
                $bilan['orphelins'][] = $orphelin;
            }
        }

        if ($bilan['orphelins'] !== []) {
            Log::warning('Deplacement d evaluation : agregats desormais sans note, laisses en place', [
                'evaluation_id' => $evaluation->id,
                'avant' => $avant,
                'apres' => $apres,
                'orphelins' => $bilan['orphelins'],
                'remede' => 'evaluations:sync-notes --clean-resultats, borne sur la classe et la periode',
            ]);
        }

        return $bilan;
    }

    /**
     * @param  array<string,mixed>  $coordonnee
     * @param  array{recalcules:int, orphelins:array<int,array<string,mixed>>, echecs:int}  $bilan
     */
    private static function executer(int $etudiantId, array $coordonnee, ?int $declencheur, array &$bilan): bool
    {
        if (! self::coordonneeComplete($coordonnee)) {
            return false;
        }

        try {
            RecomputeStudentResultatJob::dispatchSync(
                etudiantId: $etudiantId,
                classeId: (int) $coordonnee['classe_id'],
                matiereId: (int) $coordonnee['matiere_id'],
                anneeUniversitaireId: (int) $coordonnee['annee_universitaire_id'],
                periode: (string) $coordonnee['periode'],
                source: self::SOURCE,
                triggeredBy: $declencheur,
            );

            return true;
        } catch (\Throwable $e) {
            // Le deplacement, lui, a reussi et reste acquis : un recalcul qui
            // echoue ne doit pas le defaire ni faire echouer la requete.
            $bilan['echecs']++;

            Log::error('Deplacement d evaluation : recalcul en echec', [
                'etudiant_id' => $etudiantId,
                'coordonnee' => $coordonnee,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @param array<string,mixed> $coordonnee */
    private static function porteEncoreDesNotes(int $etudiantId, array $coordonnee): bool
    {
        return ESBTPNote::query()
            ->where('etudiant_id', $etudiantId)
            ->whereHas('evaluation', function ($q) use ($coordonnee) {
                $q->where('classe_id', $coordonnee['classe_id'])
                    ->where('matiere_id', $coordonnee['matiere_id'])
                    ->where('annee_universitaire_id', $coordonnee['annee_universitaire_id'])
                    ->where('periode', $coordonnee['periode'])
                    ->where('status', '!=', 'cancelled');
            })
            ->exists();
    }

    /**
     * @param  array<string,mixed>  $coordonnee
     * @return array<string,mixed>|null
     */
    private static function agregatOrphelin(int $etudiantId, array $coordonnee): ?array
    {
        $ligne = ESBTPResultat::query()
            ->where('etudiant_id', $etudiantId)
            ->where('classe_id', $coordonnee['classe_id'])
            ->where('matiere_id', $coordonnee['matiere_id'])
            ->where('annee_universitaire_id', $coordonnee['annee_universitaire_id'])
            ->where('periode', $coordonnee['periode'])
            ->first();

        if (! $ligne) {
            return null;
        }

        return [
            'resultat_id' => (int) $ligne->id,
            'etudiant_id' => $etudiantId,
            'matiere_id' => (int) $coordonnee['matiere_id'],
            'periode' => (string) $coordonnee['periode'],
            'moyenne' => $ligne->moyenne !== null ? (float) $ligne->moyenne : null,
        ];
    }

    /** @param array<string,mixed> $coordonnee */
    private static function coordonneeComplete(array $coordonnee): bool
    {
        foreach (['classe_id', 'matiere_id', 'annee_universitaire_id', 'periode'] as $cle) {
            if (empty($coordonnee[$cle])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string,mixed>  $avant
     * @param  array<string,mixed>  $apres
     */
    private static function memeCoordonnee(array $avant, array $apres): bool
    {
        foreach (['classe_id', 'matiere_id', 'annee_universitaire_id', 'periode'] as $cle) {
            if (($avant[$cle] ?? null) != ($apres[$cle] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
