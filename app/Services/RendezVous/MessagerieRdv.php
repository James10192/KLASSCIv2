<?php

namespace App\Services\RendezVous;

use App\Contracts\PorteurDeRendezVous;
use App\Helpers\SettingsHelper;
use App\Jobs\EnvoyerConvocationRdvJob;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPRdvReservation;
use App\Services\MailPulse\MailPulseClient;
use App\Services\Vitrine\IdentitePublique;
use Illuminate\Support\Facades\Log;

class MessagerieRdv
{
    public function __construct(
        private readonly IdentitePublique $identite,
        private readonly ConvocationRdvPdf $pdf,
        private readonly MailPulseClient $mailpulse,
    ) {
    }

    public function confirmer(ESBTPRdvReservation $reservation, string $action = 'confirme'): void
    {
        EnvoyerConvocationRdvJob::dispatch($reservation->id, $action);
    }

    public function expedierConvocation(ESBTPRdvReservation $reservation, string $action = 'confirme'): void
    {
        $email = trim((string) ($reservation->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->expedierViaMailPulse($email, $this->donneesConvocation($reservation, $action));
    }

    /**
     * @param  array<string, mixed>  $donnees
     */
    private function expedierViaMailPulse(string $email, array $donnees): void
    {
        $texte = $donnees['sujet']."\n\n".$donnees['date'].' '.$donnees['heure']."\nRéférence : ".$donnees['reference'];
        $html = $this->htmlMailPulse($donnees);
        $resultat = $this->mailpulse->sendEmailMessage([
            'channel' => 'email',
            'recipient' => ['type' => 'email', 'value' => $email],
            'content' => [
                'type' => 'text',
                'text' => $texte,
            ],
            'metadata' => [
                'source' => 'klassci',
                'workflow_event' => 'rendez_vous',
                'subject' => $donnees['sujet'],
                'email_html' => $html,
            ],
        ]);
        if (! $resultat->ok) {
            Log::warning('MailPulse convocation rdv refusee', [
                'status' => $resultat->status,
                'message' => $resultat->message,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $donnees
     */
    private function htmlMailPulse(array $donnees): string
    {
        $fond = htmlspecialchars((string) $donnees['emailHeaderBgColor'], ENT_QUOTES, 'UTF-8');
        $texteBandeau = htmlspecialchars((string) $donnees['emailHeaderTextColor'], ENT_QUOTES, 'UTF-8');
        $primaire = htmlspecialchars((string) $donnees['emailPrimaryColor'], ENT_QUOTES, 'UTF-8');
        $ecole = htmlspecialchars((string) $donnees['schoolName'], ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars((string) $donnees['date'], ENT_QUOTES, 'UTF-8');
        $heure = htmlspecialchars((string) $donnees['heure'], ENT_QUOTES, 'UTF-8');
        $nom = htmlspecialchars((string) $donnees['nom'], ENT_QUOTES, 'UTF-8');
        $reference = htmlspecialchars((string) $donnees['reference'], ENT_QUOTES, 'UTF-8');
        $lien = htmlspecialchars((string) $donnees['lien'], ENT_QUOTES, 'UTF-8');
        $logo = $this->identite->decrire()['logo']['url'] ?? null;
        $logoImg = is_string($logo) && $logo !== ''
            ? '<img src="'.htmlspecialchars($logo, ENT_QUOTES, 'UTF-8').'" alt="" width="72" height="72" style="display:block;margin:0 auto 8px;background:#fff;padding:4px;border-radius:6px;">'
            : '';

        return '<!DOCTYPE html><html><body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;">'
            .'<table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:16px auto;background:#fff;">'
            .'<tr><td style="background:'.$fond.';color:'.$texteBandeau.';padding:20px 16px;text-align:center;">'
            .$logoImg
            .'<div style="font-size:18px;font-weight:bold;">'.$ecole.'</div>'
            .'<div style="font-size:13px;margin-top:8px;">Convocation au guichet</div>'
            .'</td></tr>'
            .'<tr><td style="padding:20px 16px;color:#1f2937;">'
            .'<p>Bonjour '.$nom.',</p>'
            .'<p>Votre rendez-vous est confirmé.</p>'
            .'<p style="font-size:18px;font-weight:bold;color:'.$primaire.';">'.$date.'<br>'.$heure.'</p>'
            .($reference !== '' ? '<p>Référence : <strong>'.$reference.'</strong></p>' : '')
            .'<p>Présentez-vous avec vos pièces. Un PDF de convocation n\'est pas joint : conservez cet e-mail.</p>'
            .($lien !== '' ? '<p><a href="'.$lien.'" style="color:'.$primaire.';">Voir ou modifier le rendez-vous</a></p>' : '')
            .'</td></tr></table></body></html>';
    }

    /**
     * @return array<string, mixed>
     */
    private function donneesConvocation(ESBTPRdvReservation $reservation, string $action): array
    {
        $creneau = $reservation->creneau;
        $ecole = SettingsHelper::getSchoolInfo();
        $pdf = SettingsHelper::getPdfSettings();
        $reference = $reservation->porteur()?->referencePubliqueAffichee() ?? '';
        $nomEcole = trim((string) ($ecole['name'] ?? '')) ?: $this->nomEcole();

        $intro = match ($action) {
            'deplace' => 'Votre rendez-vous a été déplacé.',
            'annule' => 'Votre rendez-vous a été annulé.',
            default => 'Votre rendez-vous est confirmé.',
        };

        return [
            'sujet' => $intro.' — '.$nomEcole,
            'parentName' => $reservation->prenoms ?: $reservation->nom,
            'nom' => trim($reservation->nom.' '.$reservation->prenoms),
            'date' => $creneau?->date?->translatedFormat('l j F Y') ?? '—',
            'heure' => $creneau ? ($creneau->heureDebutHi().' – '.$creneau->heureFinHi()) : '—',
            'reference' => $reference,
            'lien' => $this->lienReservation($reference),
            'schoolName' => $nomEcole,
            'schoolAddress' => $ecole['address'] ?? '',
            'schoolPhone' => $ecole['phone'] ?? '',
            'schoolEmail' => $ecole['email'] ?? '',
            'schoolLogoPath' => SettingsHelper::resolveLogoPath(),
            'emailPrimaryColor' => $pdf['primary_color'] ?? '#0453cb',
            'emailHeaderBgColor' => $pdf['header_bg_color'] ?? ($pdf['primary_color'] ?? '#0453cb'),
            'emailHeaderTextColor' => $pdf['header_text_on_bg'] ?? ($pdf['header_text_color'] ?? '#ffffff'),
            'emailSecondaryColor' => $pdf['secondary_color'] ?? '#64748b',
        ];
    }

    /**
     * @return array{envoyes: int, sans_email: int, deja: int}
     */
    public function inviterEnAttente(bool $ecrire = false): array
    {
        $rapport = ['envoyes' => 0, 'sans_email' => 0, 'deja' => 0];

        ESBTPCandidature::query()
            ->where('statut', ESBTPCandidature::STATUT_EN_ATTENTE)
            ->whereDoesntHave('reservations', fn ($q) => $q->occupantes())
            ->orderBy('id')
            ->each(function (ESBTPCandidature $c) use ($ecrire, &$rapport) {
                $this->inviterPorteur($c, $ecrire, $rapport);
            });

        ESBTPReinscriptionDemande::query()
            ->where('statut', ESBTPReinscriptionDemande::STATUT_EN_ATTENTE)
            ->whereDoesntHave('reservations', fn ($q) => $q->occupantes())
            ->with('etudiant')
            ->orderBy('id')
            ->each(function (ESBTPReinscriptionDemande $d) use ($ecrire, &$rapport) {
                $this->inviterPorteur($d, $ecrire, $rapport);
            });

        return $rapport;
    }

    /**
     * @param  array{envoyes: int, sans_email: int, deja: int}  $rapport
     */
    private function inviterPorteur(PorteurDeRendezVous $porteur, bool $ecrire, array &$rapport): void
    {
        if ($porteur->dejaInviteRdv()) {
            $rapport['deja']++;

            return;
        }

        $email = trim((string) $porteur->emailRdv());
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $rapport['sans_email']++;

            return;
        }

        if (! $ecrire) {
            $rapport['envoyes']++;

            return;
        }

        $porteur->marquerInviteRdv();
        $rapport['envoyes']++;
    }

    private function nomEcole(): string
    {
        $nom = trim((string) ($this->identite->decrire()['nom'] ?? ''));

        return $nom !== '' ? $nom : 'votre établissement';
    }

    private function lienReservation(string $referenceAffichee): string
    {
        $code = strtolower(trim((string) config('app.tenant_code', '')));

        return 'https://www.klassci.com/inscription/universite/'.$code.'/rendez-vous?ref='
            .rawurlencode($referenceAffichee);
    }
}
