<?php

declare(strict_types=1);

namespace App\Domain\Notes\Reclamations;

use App\Models\ESBTPReclamationNote;
use App\Models\User;
use App\Services\NotificationService;

/**
 * Qui est prévenu, à chaque étape. Les textes restent ici pour ne pas
 * s'éparpiller dans trois actions.
 *
 * Une notification ratée est journalisée par NotificationService et ne fait
 * jamais échouer l'étape : la réclamation, elle, est enregistrée.
 */
final class NotificationsReclamation
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly DestinatairesReclamation $destinataires,
    ) {}

    public function deposee(ESBTPReclamationNote $r, User $eleve): void
    {
        $titre = 'Nouvelle réclamation de note';
        $message = $this->quiQuoi($r).' conteste sa note ('.$this->valeur($r->note_initiale).').';
        $lien = route('esbtp.reclamations-notes.index', ['reclamation' => $r->id]);

        $deja = [];
        if ($r->enseignant) {
            $this->notifications->createNotification($r->enseignant, $titre, $message.' Votre avis est attendu.', 'warning', $lien, $eleve);
            $deja[] = (int) $r->enseignant->id;
        }
        foreach ($this->destinataires->traitants() as $u) {
            if (! in_array((int) $u->id, $deja, true)) {
                $this->notifications->createNotification($u, $titre, $message, 'warning', $lien, $eleve);
                $deja[] = (int) $u->id;
            }
        }
    }

    public function avisDonne(ESBTPReclamationNote $r, User $enseignant): void
    {
        $avis = $r->avis === ESBTPReclamationNote::AVIS_CORRIGER
            ? 'propose '.$this->valeur($r->note_proposee)
            : 'confirme la note';
        $lien = route('esbtp.reclamations-notes.index', ['reclamation' => $r->id]);

        foreach ($this->destinataires->traitants() as $u) {
            if ((int) $u->id !== (int) $enseignant->id) {
                $this->notifications->createNotification($u, 'Réclamation : avis de l\'enseignant', $enseignant->name.' '.$avis.' — '.$this->quiQuoi($r).'. À valider.', 'info', $lien, $enseignant);
            }
        }

        if ($eleve = $r->etudiant?->user) {
            $this->notifications->createNotification($eleve, 'Votre réclamation avance', 'L\'enseignant a donné son avis sur votre note de '.$this->matiere($r).'. Le personnel habilité va trancher.', 'info', route('esbtp.mes-reclamations.index'), $enseignant);
        }
    }

    public function tranchee(ESBTPReclamationNote $r, User $auteur): void
    {
        $acceptee = $r->statut === \App\Enums\StatutReclamationNote::ACCEPTEE;
        $texte = $acceptee
            ? 'Votre note de '.$this->matiere($r).' passe de '.$this->valeur($r->note_initiale).' à '.$this->valeur($r->note_finale).'.'
            : 'Votre note de '.$this->matiere($r).' est maintenue. '.$r->commentaire_decision;

        if ($eleve = $r->etudiant?->user) {
            $this->notifications->createNotification($eleve, $acceptee ? 'Réclamation acceptée' : 'Réclamation refusée', $texte, $acceptee ? 'success' : 'info', route('esbtp.mes-reclamations.index'), $auteur);
        }

        if ($r->enseignant && (int) $r->enseignant->id !== (int) $auteur->id) {
            $this->notifications->createNotification($r->enseignant, 'Réclamation tranchée', $this->quiQuoi($r).' : '.($acceptee ? 'note corrigée à '.$this->valeur($r->note_finale) : 'note maintenue').'.', 'info', route('esbtp.reclamations-notes.index', ['reclamation' => $r->id]), $auteur);
        }
    }

    private function quiQuoi(ESBTPReclamationNote $r): string
    {
        $eleve = trim(($r->etudiant->nom ?? '').' '.($r->etudiant->prenoms ?? ''));

        return $eleve.' ('.$this->matiere($r).', '.($r->evaluation->titre ?? 'évaluation').')';
    }

    private function matiere(ESBTPReclamationNote $r): string
    {
        return (string) ($r->matiere->name ?? 'matière');
    }

    private function valeur(mixed $note): string
    {
        return $note === null ? 'absent' : number_format((float) $note, 2, ',', ' ');
    }
}
