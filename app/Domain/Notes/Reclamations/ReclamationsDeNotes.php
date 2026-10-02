<?php

declare(strict_types=1);

namespace App\Domain\Notes\Reclamations;

use App\Domain\Notes\CorrectionDeNotes;
use App\Enums\StatutReclamationNote;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPNote;
use App\Models\ESBTPReclamationNote;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Les trois gestes d'une réclamation : déposer, donner son avis, trancher.
 *
 * Un seul service plutôt que trois classes : les trois partagent les mêmes
 * gardes (statut ouvert, verrou de ligne) et la même notification, et aucun
 * ne dépasse vingt lignes.
 */
final class ReclamationsDeNotes
{
    public const DISQUE = 'local';

    public function __construct(
        private readonly ReglagesReclamations $reglages,
        private readonly DestinatairesReclamation $destinataires,
        private readonly NotificationsReclamation $notifications,
        private readonly CorrectionDeNotes $correction,
    ) {}

    /** Pourquoi cette note ne peut pas être contestée maintenant, ou null. */
    public function refusDeDepot(ESBTPNote $note, ESBTPEtudiant $etudiant): ?string
    {
        return match (true) {
            ! $this->reglages->actives() => 'Les réclamations de notes ne sont pas ouvertes dans votre établissement.',
            (int) $note->etudiant_id !== (int) $etudiant->id => 'Cette note ne vous appartient pas.',
            ! $note->evaluation => 'Cette note n\'est plus rattachée à une évaluation.',
            self::fermeLe($note, $this->reglages->delaiJours())?->isPast() === true
                => 'Le délai de '.$this->reglages->delaiJours().' jours pour contester cette note est dépassé.',
            ESBTPReclamationNote::withTrashed()->where('note_id', $note->id)->exists()
                => 'Cette note a déjà fait l\'objet d\'une réclamation : un seul recours par note.',
            default => null,
        };
    }

    /**
     * La fin du delai de recours. Il court depuis la SAISIE de la note
     * (`created_at`), pas depuis sa derniere ecriture : une correction, un
     * recalcul ou la decision d'une reclamation reecrivent `updated_at`, et
     * rouvriraient des notes closes depuis des mois.
     */
    public static function fermeLe(ESBTPNote $note, int $delaiJours): ?\Illuminate\Support\Carbon
    {
        return $note->created_at?->copy()->addDays($delaiJours);
    }

    public function deposer(ESBTPNote $note, ESBTPEtudiant $etudiant, string $motif, UploadedFile $photo, User $auteur): ESBTPReclamationNote
    {
        $note->loadMissing('evaluation.classe', 'evaluation.matiere');
        if (($refus = $this->refusDeDepot($note, $etudiant)) !== null) {
            throw ValidationException::withMessages(['note_id' => $refus]);
        }

        $evaluation = $note->evaluation;
        $chemin = $photo->store('reclamations-notes/'.$etudiant->id, self::DISQUE);

        try {
            $reclamation = ESBTPReclamationNote::create([
                'note_id' => $note->id,
                'etudiant_id' => $etudiant->id,
                'evaluation_id' => $evaluation->id,
                'matiere_id' => $evaluation->matiere_id,
                'classe_id' => $evaluation->classe_id,
                'annee_universitaire_id' => $evaluation->annee_universitaire_id,
                'enseignant_id' => $this->destinataires->enseignantDe($evaluation)?->id,
                'note_initiale' => $note->is_absent ? null : $note->note,
                'motif' => trim($motif),
                'photo_path' => $chemin,
                'statut' => StatutReclamationNote::SOUMISE,
            ]);
        } catch (\Throwable $e) {
            // Pas de photo orpheline sur le disque si la ligne n'est pas écrite.
            Storage::disk(self::DISQUE)->delete($chemin);
            // Deux envois simultanes : l'index unique sur note_id departage.
            if ($e instanceof \Illuminate\Database\QueryException && ($e->errorInfo[0] ?? null) === '23000') {
                throw ValidationException::withMessages(['note_id' => 'Une réclamation vient déjà d\'être déposée sur cette note.']);
            }
            throw $e;
        }

        $this->notifications->deposee($reclamation->load('etudiant', 'evaluation', 'matiere', 'enseignant'), $auteur);

        return $reclamation;
    }

    /** L'enseignant de l'évaluation propose ; il ne touche jamais la note. */
    public function donnerAvis(ESBTPReclamationNote $reclamation, User $enseignant, string $avis, ?float $noteProposee, string $commentaire): ESBTPReclamationNote
    {
        if (! $this->destinataires->estLEnseignant($enseignant, $reclamation)) {
            throw ValidationException::withMessages(['avis' => 'Seul l\'enseignant de cette évaluation donne son avis.']);
        }

        $reclamation = DB::transaction(function () use ($reclamation, $enseignant, $avis, $noteProposee, $commentaire) {
            $r = ESBTPReclamationNote::lockForUpdate()->findOrFail($reclamation->id);
            $this->exigerOuverte($r);
            $this->exigerDansLeBareme($r, $avis === ESBTPReclamationNote::AVIS_CORRIGER ? $noteProposee : null, 'note_proposee');

            $r->update([
                'avis' => $avis,
                'note_proposee' => $avis === ESBTPReclamationNote::AVIS_CORRIGER ? $noteProposee : null,
                'commentaire_enseignant' => trim($commentaire),
                'avis_par' => $enseignant->id,
                'avis_at' => now(),
                'statut' => StatutReclamationNote::AVIS_DONNE,
            ]);

            return $r;
        });

        $this->notifications->avisDonne($reclamation->load('etudiant.user', 'evaluation', 'matiere'), $enseignant);

        return $reclamation;
    }

    /**
     * Le personnel habilité tranche. Accepter réécrit la note par CorrectionDeNotes :
     * la moyenne est recalculée tout de suite, et la trace d'auteur est posée.
     */
    public function trancher(ESBTPReclamationNote $reclamation, User $auteur, bool $accepter, ?float $noteFinale, ?string $commentaire): ESBTPReclamationNote
    {
        $reclamation = DB::transaction(function () use ($reclamation, $auteur, $accepter, $noteFinale, $commentaire) {
            $r = ESBTPReclamationNote::lockForUpdate()->findOrFail($reclamation->id);
            $this->exigerOuverte($r);

            if ($accepter) {
                $this->exigerDansLeBareme($r, $noteFinale, 'note_finale');
                $this->correction->appliquer((int) $r->etudiant_id, [['note_id' => (int) $r->note_id, 'note' => $noteFinale]], false, (int) $auteur->id);
            }

            $r->update([
                'statut' => $accepter ? StatutReclamationNote::ACCEPTEE : StatutReclamationNote::REJETEE,
                'note_finale' => $accepter ? $noteFinale : $r->note_initiale,
                'commentaire_decision' => $commentaire !== null ? trim($commentaire) : null,
                'decision_par' => $auteur->id,
                'decision_at' => now(),
            ]);

            return $r;
        });

        $this->notifications->tranchee($reclamation->load('etudiant.user', 'evaluation', 'matiere', 'enseignant'), $auteur);

        return $reclamation;
    }

    private function exigerOuverte(ESBTPReclamationNote $r): void
    {
        if (! $r->estOuverte()) {
            throw ValidationException::withMessages(['statut' => 'Cette réclamation est déjà tranchée.']);
        }
    }

    private function exigerDansLeBareme(ESBTPReclamationNote $r, ?float $valeur, string $champ): void
    {
        if ($valeur === null) {
            return;
        }
        $bareme = (float) ($r->evaluation->bareme ?? 20) ?: 20.0;
        if ($valeur < 0 || $valeur > $bareme) {
            throw ValidationException::withMessages([$champ => 'La note doit être comprise entre 0 et '.rtrim(rtrim(number_format($bareme, 2, '.', ''), '0'), '.').'.']);
        }
    }
}
