<?php

namespace App\Services\RendezVous;

use App\Contracts\PorteurDeRendezVous;
use App\Helpers\SettingsHelper;
use App\Jobs\EnvoyerConvocationRdvJob;
use App\Mail\Parents\ConvocationRdvMail;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPRdvReservation;
use App\Services\MailPulse\MailPulseClient;
use App\Services\Vitrine\IdentitePublique;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

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

        $donnees = $this->donneesConvocation($reservation, $action);
        $pdfBinaire = $action === 'annule' ? '' : $this->pdf->binaire($reservation);

        try {
            Mail::to($email)->send(new ConvocationRdvMail($donnees, $pdfBinaire));
        } catch (\Throwable $e) {
            $this->expedierViaMailPulse($email, $donnees, $pdfBinaire);
            if (! str_contains($e->getMessage(), '550')) {
                Log::warning('SMTP convocation rdv, repli MailPulse', ['erreur' => $e->getMessage()]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $donnees
     */
    private function expedierViaMailPulse(string $email, array $donnees, string $pdfBinaire): void
    {
        $html = View::make('esbtp.emails.parents.rendez-vous-convocation', $donnees + [
            'message' => new class($donnees['schoolLogoPath'] ?? null)
            {
                public function __construct(private readonly ?string $logo)
                {
                }

                public function embed(string $path): string
                {
                    $fichier = $this->logo && is_file($this->logo) ? $this->logo : $path;
                    if (! is_file($fichier)) {
                        return $path;
                    }
                    $mime = mime_content_type($fichier) ?: 'image/png';

                    return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($fichier));
                }
            },
        ])->render();

        $message = [
            'channel' => 'email',
            'recipient' => ['type' => 'email', 'value' => $email],
            'subject' => $donnees['sujet'],
            'content' => [
                'type' => 'html',
                'html' => $html,
            ],
            'metadata' => ['source' => 'klassci', 'workflow_event' => 'rendez_vous'],
        ];
        if ($pdfBinaire !== '') {
            $message['attachments'] = [[
                'filename' => 'convocation-rendez-vous.pdf',
                'content' => base64_encode($pdfBinaire),
            ]];
        }

        $resultat = $this->mailpulse->sendEmailMessage($message);
        if ($resultat->ok) {
            return;
        }

        unset($message['attachments']);
        $resultat = $this->mailpulse->sendEmailMessage($message);
        if (! $resultat->ok) {
            Log::warning('MailPulse convocation rdv refusee', [
                'status' => $resultat->status,
                'message' => $resultat->message,
            ]);
        }
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
