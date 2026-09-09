<?php

namespace App\Services\RendezVous;

use App\Jobs\EnvoyerConvocationRdvJob;
use App\Models\ESBTPRdvReservation;
use App\Services\MailPulse\MailPulseClient;
use App\Services\Vitrine\IdentitePublique;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\View;
use RuntimeException;

class MessagerieRdv
{
    public function __construct(
        private readonly IdentitePublique $identite,
        private readonly MailPulseClient $mailpulse,
    ) {
    }

    public function confirmer(ESBTPRdvReservation $reservation, string $action = 'confirme'): void
    {
        EnvoyerConvocationRdvJob::dispatch($reservation->id, $action);
    }

    public function expedierConvocation(ESBTPRdvReservation $reservation, string $action = 'confirme'): bool
    {
        $email = trim((string) ($reservation->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $donnees = $this->donneesConvocation($reservation, $action);
        $texte = $donnees['sujet']."\n\n".$donnees['date'].' '.$donnees['heure']."\nRéférence : ".$donnees['reference'];
        $html = View::make('esbtp.emails.parents.rendez-vous-convocation', $donnees)->render();
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

        if ($resultat->ok) {
            return true;
        }

        if ($resultat->status === 'disabled') {
            return false;
        }

        throw new RuntimeException('MailPulse rdv: '.$resultat->status);
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
