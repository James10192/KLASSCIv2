<?php

namespace App\Observers;

use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPNote;
use Illuminate\Support\Facades\Log;

/**
 * Déclenche un recalcul automatique du résultat de l'étudiant à chaque
 * sauvegarde / suppression d'une note, pour garantir des bulletins
 * toujours synchronisés avec la dernière saisie.
 *
 * Le job est dispatché en `afterCommit()` afin que la note soit bien
 * persistée avant que le recalcul ne lise la base.
 *
 * Bypass : si la propriété statique {@see static::$muted} est true, aucun
 * job n'est dispatché. Utile pour la commande `notes:recompute` qui pilote
 * le recalcul elle-même et veut éviter une cascade.
 */
class ESBTPNoteObserver
{
    /**
     * Quand true, désactive le dispatch du Job.
     * Utilisé par la commande artisan pour pilotage manuel.
     */
    public static bool $muted = false;

    public function saved(ESBTPNote $note): void
    {
        $this->dispatchRecompute($note);
    }

    public function deleted(ESBTPNote $note): void
    {
        $this->dispatchRecompute($note);
    }

    public function restored(ESBTPNote $note): void
    {
        $this->dispatchRecompute($note);
    }

    /**
     * Dispatche le job de recalcul si le contexte est complet.
     *
     * On no-op silencieusement (pas d'exception) si l'évaluation est
     * orpheline ou incomplète — la saisie reste persistée, juste pas
     * de recalcul auto possible.
     *
     * NB : on préfère `loadMissing` à `$note->evaluation` direct pour
     * éviter une query supplémentaire si l'évaluation est déjà eager-loadée
     * par le caller (typique en saisie bulk).
     */
    private function dispatchRecompute(ESBTPNote $note): void
    {
        if (static::$muted) {
            return;
        }

        try {
            $note->loadMissing('evaluation:id,classe_id,matiere_id,annee_universitaire_id,periode');
            $evaluation = $note->evaluation;

            if (! $evaluation) {
                return;
            }

            $classeId = $evaluation->classe_id;
            $matiereId = $evaluation->matiere_id;
            $anneeId = $evaluation->annee_universitaire_id;
            $periode = $evaluation->periode;

            if (! $classeId || ! $matiereId || ! $anneeId || ! $periode) {
                return;
            }

            RecomputeStudentResultatJob::dispatch(
                etudiantId: (int) $note->etudiant_id,
                classeId: (int) $classeId,
                matiereId: (int) $matiereId,
                anneeUniversitaireId: (int) $anneeId,
                periode: (string) $periode,
                source: 'observer',
                triggeredBy: auth()->id(),
            )->afterCommit();
        } catch (\Throwable $e) {
            // Ne JAMAIS bloquer le save() de la note pour un échec de dispatch.
            Log::warning('ESBTPNoteObserver: dispatch failed', [
                'note_id' => $note->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
