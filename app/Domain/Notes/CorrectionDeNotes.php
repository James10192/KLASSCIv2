<?php

namespace App\Domain\Notes;

use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Observers\ESBTPNoteObserver;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Corriger des notes EXISTANTES d'un eleve, puis recalculer ses moyennes.
 *
 * Preferable a la saisie d'une moyenne quand la matiere n'a qu'une note : la
 * note et la moyenne restent d'accord, et un recalcul ulterieur ne defait pas
 * la correction (il repartirait des notes corrigees).
 *
 * Le recalcul est SYNCHRONE. L'observateur des notes le confie a une file,
 * et rien ne garantit qu'un worker tourne : la moyenne enregistree d'avant
 * resterait en place, et l'emporterait au bulletin. L'observateur est donc
 * muet pendant l'ecriture, et chaque coordonnee touchee est recalculee ici.
 *
 * Le commentaire de la note n'est pas touche : c'est la trace de l'enseignant.
 */
final class CorrectionDeNotes
{
    /**
     * @param  array<int, array{note_id:int, note:float|int|string}>  $lignes
     * @return array{lignes: array<int, array<string,mixed>>, moyennes: array<int, array<string,mixed>>}
     */
    public function appliquer(int $etudiantId, array $lignes, bool $simuler, ?int $auteurId): array
    {
        $cibles = [];
        foreach ($lignes as $ligne) {
            $note = ESBTPNote::with(['evaluation:id,titre,bareme,classe_id,matiere_id,annee_universitaire_id,periode', 'matiere:id,name'])
                ->find($ligne['note_id']);
            $this->refuserSiInvalide($note, $ligne, $etudiantId);
            $cibles[] = [$note, round((float) $ligne['note'], 2)];
        }

        $rapport = array_map(fn (array $c) => [
            'note_id' => $c[0]->id,
            'matiere' => $c[0]->matiere?->name,
            'evaluation' => $c[0]->evaluation->titre,
            'periode' => $c[0]->evaluation->periode,
            'avant' => $c[0]->is_absent ? 'absent' : (float) $c[0]->note,
            'apres' => $c[1],
        ], $cibles);

        if ($simuler) {
            return ['lignes' => $rapport, 'moyennes' => []];
        }

        ESBTPNoteObserver::$muted = true;
        try {
            DB::transaction(function () use ($cibles, $auteurId) {
                foreach ($cibles as [$note, $valeur]) {
                    $note->note = $valeur;
                    $note->is_absent = false;
                    $note->updated_by = $auteurId;
                    $note->save();
                }
            });
        } finally {
            ESBTPNoteObserver::$muted = false;
        }

        return ['lignes' => $rapport, 'moyennes' => $this->recalculer($etudiantId, $cibles, $auteurId)];
    }

    /** @param array{note_id:int, note:float|int|string} $ligne */
    private function refuserSiInvalide(?ESBTPNote $note, array $ligne, int $etudiantId): void
    {
        $motif = match (true) {
            ! $note => "La note #{$ligne['note_id']} n'existe pas.",
            (int) $note->etudiant_id !== $etudiantId => "La note #{$note->id} appartient à un autre élève.",
            ! $note->evaluation => "La note #{$note->id} n'a plus d'évaluation.",
            (float) $ligne['note'] > (float) ($note->evaluation->bareme ?: 20) => "La note #{$note->id} dépasse le barème de son évaluation ({$note->evaluation->bareme}).",
            default => null,
        };

        if ($motif !== null) {
            throw ValidationException::withMessages(['notes' => $motif]);
        }
    }

    /**
     * @param  array<int, array{0: ESBTPNote, 1: float}>  $cibles
     * @return array<int, array<string,mixed>>
     */
    private function recalculer(int $etudiantId, array $cibles, ?int $auteurId): array
    {
        $coordonnees = [];
        foreach ($cibles as [$note]) {
            $e = $note->evaluation;
            $coordonnees["{$e->classe_id}|{$e->matiere_id}|{$e->annee_universitaire_id}|{$e->periode}"] = [$e, $note->matiere?->name];
        }

        $moyennes = [];
        foreach ($coordonnees as [$e, $matiere]) {
            // dispatchNow et non dispatchSync : ce dernier passe encore par le
            // gestionnaire de files (connexion « sync »), qu'une configuration
            // ou un faux de test peut intercepter. Ici on veut l'execution.
            app(Dispatcher::class)->dispatchNow(new RecomputeStudentResultatJob(
                etudiantId: $etudiantId,
                classeId: (int) $e->classe_id,
                matiereId: (int) $e->matiere_id,
                anneeUniversitaireId: (int) $e->annee_universitaire_id,
                periode: (string) $e->periode,
                source: 'cli-correction',
                triggeredBy: $auteurId,
            ));
            $moyennes[] = [
                'matiere' => $matiere,
                'periode' => $e->periode,
                'moyenne' => ESBTPResultat::where([
                    'etudiant_id' => $etudiantId,
                    'classe_id' => $e->classe_id,
                    'matiere_id' => $e->matiere_id,
                    'annee_universitaire_id' => $e->annee_universitaire_id,
                    'periode' => ESBTPEvaluation::periodeCanonique($e->periode),
                ])->value('moyenne'),
            ];
        }

        return $moyennes;
    }
}
