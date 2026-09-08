<?php

namespace App\Services\RendezVous;

use App\Mail\RendezVous\ConfirmationRdvMail;
use App\Mail\RendezVous\InvitationRdvMail;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPRdvReservation;
use App\Services\MailPulse\MailPulseClient;
use App\Services\Vitrine\IdentitePublique;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MessagerieRdv
{
    public function __construct(
        private readonly ReferencePublique $references,
        private readonly IdentitePublique $identite,
        private readonly MailPulseClient $mailpulse,
    ) {
    }

    public function confirmer(ESBTPRdvReservation $reservation, string $action = 'confirme'): void
    {
        $email = trim((string) ($reservation->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $creneau = $reservation->creneau;
        $ecole = $this->nomEcole();
        $reference = $this->referencePourReservation($reservation);

        $intro = match ($action) {
            'deplace' => 'Votre rendez-vous a été déplacé.',
            'annule' => 'Votre rendez-vous a été annulé.',
            default => 'Votre rendez-vous est confirmé.',
        };

        $donnees = [
            'sujet' => $intro.' — '.$ecole,
            'prenom' => $reservation->prenoms ?: $reservation->nom,
            'intro' => $intro,
            'ecole' => $ecole,
            'date' => $creneau?->date?->translatedFormat('l j F Y') ?? '—',
            'heure' => $creneau ? ($creneau->heureDebutHi().' – '.$creneau->heureFinHi()) : '—',
            'reference' => $this->references->formater($reference),
            'lien' => $this->lienReservation($reference),
        ];
        $texte = $intro."\n\n".$donnees['date'].' '.$donnees['heure']."\nRéférence : ".$donnees['reference']."\n".$donnees['lien'];
        $this->expedier($email, new ConfirmationRdvMail($donnees), $texte, $donnees['sujet']);
    }

    /**
     * @return array{envoyes: int, sans_email: int, deja: int, erreurs: int}
     */
    public function inviterEnAttente(bool $ecrire = false): array
    {
        $rapport = ['envoyes' => 0, 'sans_email' => 0, 'deja' => 0, 'erreurs' => 0];
        $ecole = $this->nomEcole();

        ESBTPCandidature::query()
            ->where('statut', ESBTPCandidature::STATUT_EN_ATTENTE)
            ->whereDoesntHave('reservations', fn ($q) => $q->occupantes())
            ->orderBy('id')
            ->each(function (ESBTPCandidature $c) use ($ecrire, $ecole, &$rapport) {
                $this->inviterPorteur($c, $c->email, $c->prenoms ?: $c->nom, '', $ecrire, $ecole, $rapport);
            });

        ESBTPReinscriptionDemande::query()
            ->where('statut', ESBTPReinscriptionDemande::STATUT_EN_ATTENTE)
            ->whereDoesntHave('reservations', fn ($q) => $q->occupantes())
            ->with('etudiant')
            ->orderBy('id')
            ->each(function (ESBTPReinscriptionDemande $d) use ($ecrire, $ecole, &$rapport) {
                $etudiant = $d->etudiant;
                $this->inviterPorteur(
                    $d,
                    $etudiant->email ?? null,
                    $etudiant->prenoms ?? $etudiant->nom ?? 'bonjour',
                    ' ou de votre matricule',
                    $ecrire,
                    $ecole,
                    $rapport
                );
            });

        return $rapport;
    }

    /**
     * @param  array{envoyes: int, sans_email: int, deja: int, erreurs: int}  $rapport
     */
    private function inviterPorteur(
        ESBTPCandidature|ESBTPReinscriptionDemande $porteur,
        ?string $email,
        string $prenom,
        string $identifiantAide,
        bool $ecrire,
        string $ecole,
        array &$rapport
    ): void {
        if ($porteur->rdv_invite_at !== null) {
            $rapport['deja']++;

            return;
        }

        $email = trim((string) $email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $rapport['sans_email']++;

            return;
        }

        if (! $ecrire) {
            $rapport['envoyes']++;

            return;
        }

        $reference = $porteur instanceof ESBTPCandidature
            ? $this->references->assurerCandidature($porteur)
            : $this->references->assurerDemande($porteur);

        $donnees = [
            'sujet' => 'Prenez rendez-vous — '.$ecole,
            'prenom' => $prenom,
            'ecole' => $ecole,
            'reference' => $this->references->formater($reference),
            'lien' => $this->lienReservation($reference),
            'identifiantAide' => $identifiantAide,
        ];
        $texte = "Bonjour {$prenom},\n\nPrenez rendez-vous au guichet de {$ecole}.\nRéférence : {$donnees['reference']}\n{$donnees['lien']}";
        if ($this->expedier($email, new InvitationRdvMail($donnees), $texte, $donnees['sujet'])) {
            $porteur->forceFill(['rdv_invite_at' => now()])->save();
            $rapport['envoyes']++;
        } else {
            $rapport['erreurs']++;
        }
    }

    private function expedier(string $email, Mailable $mail, string $texte, string $sujet): bool
    {
        $pulse = $this->mailpulse->sendEmailMessage([
            'channel' => 'email',
            'recipient' => ['type' => 'email', 'value' => $email],
            'content' => ['type' => 'text', 'text' => $sujet."\n\n".$texte],
            'metadata' => ['source' => 'klassci', 'workflow_event' => 'rendez_vous'],
        ]);

        if ($pulse->ok) {
            return true;
        }

        try {
            Mail::to($email)->send($mail);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Mail de rendez-vous non parti', [
                'mailpulse' => $pulse->status,
                'erreur' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function nomEcole(): string
    {
        $nom = trim((string) ($this->identite->decrire()['nom'] ?? ''));

        return $nom !== '' ? $nom : 'votre établissement';
    }

    private function lienReservation(string $reference): string
    {
        $code = strtolower(trim((string) config('app.tenant_code', '')));

        return 'https://www.klassci.com/inscription/universite/'.$code.'/rendez-vous?ref='
            .rawurlencode($this->references->formater($reference));
    }

    private function referencePourReservation(ESBTPRdvReservation $reservation): string
    {
        if ($reservation->candidature_id) {
            $c = $reservation->candidature ?? ESBTPCandidature::query()->find($reservation->candidature_id);

            return $c ? $this->references->assurerCandidature($c) : '';
        }

        $d = $reservation->demande ?? ESBTPReinscriptionDemande::query()->find($reservation->reinscription_demande_id);

        return $d ? $this->references->assurerDemande($d) : '';
    }
}
