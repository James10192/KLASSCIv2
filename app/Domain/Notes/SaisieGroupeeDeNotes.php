<?php

namespace App\Domain\Notes;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\User;
use App\Services\Notes\MotifDeRefusDeNote;
use App\Services\Notes\NoteSubmissionSynchronizationService;
use App\Services\Notes\UniciteDesNotes;
use App\Services\NotesWindowGuard;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Saisie groupée de notes : les MÊMES gardes pour l'écran de saisie et pour
 * l'assistant. Sorti de ESBTPNoteController (saveNotesAjaxBulk) pour qu'un
 * second chemin d'écriture ne puisse pas oublier une garde.
 *
 * Gardes, dans l'ordre (MotifDeRefusDeNote) : évaluation publiée, droit de
 * saisir sur cette évaluation (fenêtre de saisie, notes.edit, ou ses propres
 * évaluations), étudiant de la cohorte, note dans le barème, note déjà validée
 * modifiable seulement avec notes.edit.
 *
 * analyser() ne touche à rien : c'est l'aperçu. enregistrer() écrit dans une
 * transaction et n'envoie les avis d'absence qu'après le commit.
 */
class SaisieGroupeeDeNotes
{
    public function __construct(
        private NotesWindowGuard $fenetre,
        private NoteSubmissionSynchronizationService $synchronisation,
        private NotificationService $notifications,
    ) {
    }

    public function peutGerer(?User $user, ESBTPEvaluation $evaluation): bool
    {
        if (! $user) {
            return false;
        }

        if (! $this->fenetre->canWrite($user, (int) $evaluation->classe_id)) {
            return false;
        }

        if ($user->can('notes.edit')) {
            return true;
        }

        if (! $user->can('notes.manage_own')) {
            return $user->can('notes.create');
        }

        if (! $user->can('identity.teach')) {
            return true;
        }

        return (int) $evaluation->enseignant_id === (int) $user->id
            || (int) $evaluation->created_by === (int) $user->id;
    }

    /**
     * Ce que ferait la saisie, sans rien écrire.
     *
     * @param array<int, array{etudiant_id:int, evaluation_id:int, note?:mixed, is_absent?:bool}> $entrees
     * @return array<int, array{entree:array, statut:string, raison:?string, avant:?array}>
     *         statut : creation | modification | inchange | refus
     */
    public function analyser(array $entrees, User $user): array
    {
        $evaluations = ESBTPEvaluation::whereIn('id', collect($entrees)->pluck('evaluation_id')->unique())->get()->keyBy('id');
        $existantes = $this->notesExistantes($entrees);
        $motifs = app(MotifDeRefusDeNote::class);
        $peutGerer = fn (ESBTPEvaluation $e) => $this->peutGerer($user, $e);
        $modifierValidee = $user->can('notes.edit');

        return array_map(function (array $entree) use ($evaluations, $existantes, $motifs, $peutGerer, $modifierValidee) {
            $evaluation = $evaluations->get($entree['evaluation_id']);
            $note = $existantes->get($entree['etudiant_id'] . '_' . $entree['evaluation_id']);
            $raison = $motifs->pour($entree, $evaluation, $note, $modifierValidee, $peutGerer);
            $avant = $note ? ['note' => $note->is_absent ? null : (float) $note->note, 'absent' => (bool) $note->is_absent, 'validee' => $note->isSubmitted()] : null;

            $statut = match (true) {
                $raison !== null => 'refus',
                $note === null => 'creation',
                $this->identique($note, $entree) => 'inchange',
                default => 'modification',
            };

            return ['entree' => $entree, 'statut' => $statut, 'raison' => $raison, 'avant' => $avant];
        }, $entrees);
    }

    /**
     * @return array{saved:int, refused:array, total:int, synchronization:array}
     * @throws \Throwable rien n'est écrit si une exception remonte
     */
    public function enregistrer(array $entrees, User $user, bool $valider): array
    {
        $saved = 0;
        // Les paires refusées, avec leur raison : l'écran les garde en brouillon
        // au lieu de les écraser par la relecture du serveur.
        $refused = [];
        $avis = [];

        $synchronization = DB::transaction(function () use ($entrees, $user, $valider, &$saved, &$refused, &$avis) {
            $evaluations = ESBTPEvaluation::whereIn('id', collect($entrees)->pluck('evaluation_id')->unique())->get()->keyBy('id');
            $existantes = $this->notesExistantes($entrees);
            $modifierValidee = $user->can('notes.edit');
            $motifs = app(MotifDeRefusDeNote::class);
            $peutGerer = fn (ESBTPEvaluation $e) => $this->peutGerer($user, $e);

            foreach ($entrees as $entree) {
                $evaluation = $evaluations->get($entree['evaluation_id']);
                $note = $existantes->get($entree['etudiant_id'] . '_' . $entree['evaluation_id']);

                $raison = $motifs->pour($entree, $evaluation, $note, $modifierValidee, $peutGerer);
                if ($raison !== null) {
                    $refused[] = MotifDeRefusDeNote::ligne($entree, $raison);
                    continue;
                }

                $resultat = $this->ecrireSansDoublon($evaluation, $note, $entree, $valider, $user);
                if ($resultat === null) {
                    $refused[] = MotifDeRefusDeNote::ligne($entree, MotifDeRefusDeNote::SAISIE_CONCURRENTE);
                    continue;
                }

                if ($resultat['is_new_absent']) {
                    $avis[] = [$resultat['note'], $evaluation];
                }
                $saved++;
            }

            return $this->synchronisation->apresValidation($user, $valider, count($refused), $motifs->evaluationsAutorisees());
        });

        // Après le commit : un avis envoyé ne se rattrape pas si la transaction échoue.
        foreach ($avis as [$note, $evaluation]) {
            $this->notifierAbsence($note, $evaluation);
        }

        return ['saved' => $saved, 'refused' => $refused, 'total' => count($entrees), 'synchronization' => $synchronization];
    }

    /** Notes déjà en base pour les paires saisies, indexées « élève_évaluation ». */
    public function notesExistantes(array $entrees): Collection
    {
        // Requête tuple-based IN avec cast int pour éviter injection SQL
        $pairs = collect($entrees)
            ->map(fn ($e) => '(' . (int) $e['etudiant_id'] . ', ' . (int) $e['evaluation_id'] . ')')
            ->implode(',');

        return $pairs
            ? ESBTPNote::whereRaw("(etudiant_id, evaluation_id) IN ({$pairs})")
                ->get()->keyBy(fn ($n) => $n->etudiant_id . '_' . $n->evaluation_id)
            : collect();
    }

    /** Une note, sans jamais en créer deux pour le même élève et la même évaluation. */
    public function ecrireSansDoublon(ESBTPEvaluation $evaluation, ?ESBTPNote $note, array $entree, bool $valider, ?User $user = null): ?array
    {
        try {
            return $this->ecrire($evaluation, $note, $entree, $valider, $user);
        } catch (QueryException $e) {
            // Seul le doublon de notre index d'unicité est une saisie
            // concurrente ; toute autre violation reste une vraie erreur.
            if (($e->errorInfo[1] ?? null) !== 1062 || ! str_contains($e->getMessage(), UniciteDesNotes::INDEX)) {
                throw $e;
            }
            Log::warning('Note en double refusée : saisie concurrente', [
                'evaluation_id' => $evaluation->id,
                'etudiant_id' => $entree['etudiant_id'] ?? null,
            ]);

            return null;
        }
    }

    public function ecrire(ESBTPEvaluation $evaluation, ?ESBTPNote $existingNote, array $entry, bool $submitFinal = false, ?User $user = null): array
    {
        $auteur = $user?->id ?? auth()->id();
        $isAbsent = filter_var($entry['is_absent'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $isNew = false;

        if (! $existingNote) {
            $isNew = true;
            $existingNote = new ESBTPNote;
            $existingNote->etudiant_id = $entry['etudiant_id'];
            $existingNote->evaluation_id = $entry['evaluation_id'];
            $existingNote->classe_id = $evaluation->classe_id;
            $existingNote->matiere_id = $evaluation->matiere_id;
            $existingNote->semestre = $evaluation->periode;
            $existingNote->annee_universitaire = $evaluation->anneeUniversitaire
                ? $evaluation->anneeUniversitaire->name : 'N/A';
            $existingNote->type_evaluation = $evaluation->type;
            $existingNote->created_by = $auteur;
        } else {
            $existingNote->semestre = $evaluation->periode;
        }

        $existingNote->note = $isAbsent ? 0 : (float) ($entry['note'] ?? 0);
        $existingNote->is_absent = $isAbsent ? 1 : 0;
        $existingNote->updated_by = $auteur;
        if (isset($entry['commentaire'])) {
            $existingNote->commentaire = $entry['commentaire'];
        }

        if ($submitFinal) {
            $existingNote->submission_status = ESBTPNote::SUBMISSION_SUBMITTED;
            $existingNote->submitted_at = $existingNote->submitted_at ?: now();
            $existingNote->submitted_by = $existingNote->submitted_by ?: $auteur;
        } elseif ($isNew || ! $existingNote->isSubmitted()) {
            $existingNote->submission_status = ESBTPNote::SUBMISSION_DRAFT;
            $existingNote->submitted_at = null;
            $existingNote->submitted_by = null;
        }

        $existingNote->save();

        return ['note' => $existingNote, 'is_new_absent' => $isAbsent && $isNew];
    }

    public function notifierAbsence(ESBTPNote $note, ESBTPEvaluation $evaluation): void
    {
        try {
            // Charger l'étudiant avec sa relation user
            $etudiant = ESBTPEtudiant::with('user')->find($note->etudiant_id);

            // S'assurer que l'étudiant existe et a un compte utilisateur
            if (! $etudiant || ! $etudiant->user) {
                Log::warning("Impossible d'envoyer la notification d'absence pour la note: étudiant ou utilisateur non trouvé", [
                    'etudiant_id' => $note->etudiant_id,
                    'note_id' => $note->id,
                ]);

                return;
            }

            $matiere = $evaluation->matiere;
            $matiereName = $matiere ? $matiere->name : 'Matière non définie';

            $dateEvaluation = $evaluation->date_evaluation ? \Carbon\Carbon::parse($evaluation->date_evaluation) : \Carbon\Carbon::now();
            $jourSemaine = $dateEvaluation->locale('fr')->dayName;
            $dateFormatee = $dateEvaluation->format('d/m/Y');
            $heureFormatee = $evaluation->heure_debut ? $evaluation->heure_debut : 'Heure non définie';

            $typeActivite = 'Évaluation';
            $typeEvaluation = ucfirst($evaluation->type ?? 'évaluation');

            $messageDetail = sprintf(
                "Absence lors d'une %s (%s)\n" .
                "Matière: %s\n" .
                "Date: %s (%s)\n" .
                "Heure: %s\n" .
                'Titre: %s',
                strtolower($typeActivite),
                $typeEvaluation,
                $matiereName,
                $dateFormatee,
                ucfirst($jourSemaine),
                $heureFormatee,
                $evaluation->titre ?? 'Sans titre'
            );

            // Entrée d'absence temporaire pour la notification
            $absence = new \App\Models\ESBTPAttendance;
            $absence->date = $dateEvaluation;
            $absence->etudiant_id = $note->etudiant_id;
            $absence->statut = 'absent';
            $absence->commentaire = $messageDetail;
            $absence->matiere_id = $evaluation->matiere_id;
            $absence->type_activite = 'evaluation';
            $absence->heure_debut = $evaluation->heure_debut;
            $absence->heure_fin = $evaluation->heure_fin;

            $this->notifications->notifyNewAbsence($absence, $etudiant);

            Log::info("Notification d'absence enrichie envoyée pour la note", [
                'etudiant_id' => $note->etudiant_id,
                'note_id' => $note->id,
                'evaluation_id' => $evaluation->id,
                'matiere' => $matiereName,
                'date' => $dateFormatee,
                'jour' => $jourSemaine,
                'heure' => $heureFormatee,
                'type' => $typeEvaluation,
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de la notification d'absence pour la note", [
                'etudiant_id' => $note->etudiant_id,
                'note_id' => $note->id,
                'evaluation_id' => $evaluation->id,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
        }
    }

    private function identique(ESBTPNote $note, array $entree): bool
    {
        $absent = filter_var($entree['is_absent'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ((bool) $note->is_absent !== $absent) {
            return false;
        }

        return $absent || abs((float) $note->note - (float) ($entree['note'] ?? 0)) < 0.0001;
    }
}
