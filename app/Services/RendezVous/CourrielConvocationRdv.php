<?php

namespace App\Services\RendezVous;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPRdvReservation;
use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\MailPulseResult;
use App\Services\Vitrine\IdentitePublique;
use Illuminate\Support\Facades\View;

/**
 * Le courriel de convocation : son contenu, et sa remise a MailPulse.
 *
 * Ne decide de rien. Ce qu'il advient de la reservation selon la reponse —
 * envoyee, a relancer, en echec — est l'affaire de MessagerieRdv.
 */
class CourrielConvocationRdv
{
    public function __construct(
        private readonly IdentitePublique $identite,
        private readonly MailPulseClient $mailpulse,
        private readonly ConvocationRdvPdf $pdf,
    ) {
    }

    public function expedier(ESBTPRdvReservation $reservation, string $action): MailPulseResult
    {
        $email = trim((string) $reservation->email);

        $this->mailpulse->createOrUpdateContact([
            'email' => $email,
            'first_name' => $reservation->prenoms ?: $reservation->nom,
            'last_name' => $reservation->nom,
            'language' => 'fr',
            'preferred_channel' => 'email',
            'subscribed' => true,
            'metadata' => [
                'source' => 'klassci-rdv',
                'channel_opt_in' => ['email' => true],
            ],
        ]);

        $donnees = $this->donneesConvocation($reservation, $action);
        $texte = $donnees['sujet']."\n\n".$donnees['date'].' '.$donnees['heure']."\nRéférence : ".$donnees['reference']
            ."\nPDF : ".$donnees['lienPdf'];
        $html = View::make('esbtp.emails.parents.rendez-vous-convocation', $donnees)->render();

        return $this->mailpulse->sendEmailMessage([
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
    }

    /**
     * @return array<string, mixed>
     */
    private function donneesConvocation(ESBTPRdvReservation $reservation, string $action): array
    {
        $creneau = $reservation->creneau;
        $ecole = SettingsHelper::getSchoolInfo();
        $pdf = SettingsHelper::getPdfSettings();
        // Une convocation sans reference envoie la famille vers un lien mort (`?ref=`).
        // Les reservations anciennes relancees depuis l'ecran n'en ont pas toujours.
        $porteur = $reservation->porteur();
        $porteur?->assurerReferencePublique();
        $reference = $porteur?->referencePubliqueAffichee() ?? '';
        $nomEcole = trim((string) ($ecole['name'] ?? '')) ?: $this->nomEcole();
        $logo = $this->identite->decrire()['logo']['url'] ?? null;

        $intro = match ($action) {
            'deplace' => 'Votre rendez-vous a été déplacé.',
            'annule' => 'Votre rendez-vous a été annulé.',
            default => 'Votre rendez-vous est confirmé.',
        };

        return [
            'sujet' => $intro.' — '.$nomEcole,
            'intro' => $intro,
            'nom' => trim($reservation->nom.' '.$reservation->prenoms),
            'date' => $creneau?->date?->translatedFormat('l j F Y') ?? '—',
            'heure' => $creneau ? ($creneau->heureDebutHi().' – '.$creneau->heureFinHi()) : '—',
            'reference' => $reference,
            'lien' => $this->lienReservation($reference),
            'lienPdf' => $action === 'annule' ? '' : $this->pdf->url($reservation),
            'schoolName' => $nomEcole,
            'schoolLogoUrl' => is_string($logo) ? $logo : null,
            'emailPrimaryColor' => $pdf['primary_color'] ?? '#0453cb',
            'emailHeaderBgColor' => $pdf['header_bg_color'] ?? ($pdf['primary_color'] ?? '#0453cb'),
            'emailHeaderTextColor' => $pdf['header_text_on_bg'] ?? ($pdf['header_text_color'] ?? '#ffffff'),
        ];
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
