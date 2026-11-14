<?php

namespace App\Services\Verification;

use App\Helpers\SettingsHelper;
use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\MailPulseResult;
use App\Services\Vitrine\IdentitePublique;
use Illuminate\Support\Facades\View;

/**
 * Le courriel de verification : un code a six chiffres ET un lien. Meme
 * canal que les convocations (MailPulse), memes couleurs d'etablissement.
 *
 * Le lien porte le jeton dans le fragment (`#jeton=`) : il ne part jamais au
 * serveur du site vitrine, donc ni dans ses journaux ni dans ses mesures.
 */
class CourrielVerification
{
    public function __construct(
        private readonly MailPulseClient $mailpulse,
        private readonly IdentitePublique $identite,
    ) {}

    public function expedier(string $email, string $code, string $jeton): MailPulseResult
    {
        $donnees = $this->donnees($code, $jeton);
        $texte = $donnees['sujet']."\n\nVotre code : ".$code."\n\nOu ouvrez ce lien : ".$donnees['lien']
            ."\n\nLe code expire dans ".$donnees['minutes'].' minutes, le lien dans '.$donnees['heures'].' heures.';

        return $this->mailpulse->sendEmailMessage([
            'channel' => 'email',
            'recipient' => ['type' => 'email', 'value' => $email],
            'content' => ['type' => 'text', 'text' => $texte],
            'metadata' => [
                'source' => 'klassci',
                'workflow_event' => 'verification_contact',
                'subject' => $donnees['sujet'],
                'email_html' => View::make('esbtp.emails.verification-contact', $donnees)->render(),
            ],
        ]);
    }

    /**
     * Le code qui ouvre la convocation d'un dossier (`AccesRendezVous`).
     *
     * Un code seul, sans lien : il se saisit sur la page deja ouverte, et un
     * lien qui ouvrirait la convocation depuis la boite de reception vaudrait
     * moins qu'un code saisi la ou on l'a demande.
     */
    public function expedierCodeAcces(string $email, string $code, int $minutes): MailPulseResult
    {
        $donnees = ['minutes' => $minutes] + $this->donnees($code, '');
        $donnees['sujet'] = 'Votre code pour voir votre convocation · '.$donnees['schoolName'];
        $texte = $donnees['sujet']."\n\nVotre code : ".$code."\n\nIl expire dans ".$minutes.' minutes.'
            ."\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.";

        return $this->mailpulse->sendEmailMessage([
            'channel' => 'email',
            'recipient' => ['type' => 'email', 'value' => $email],
            'content' => ['type' => 'text', 'text' => $texte],
            'metadata' => [
                'source' => 'klassci',
                'workflow_event' => 'acces_rendez_vous',
                'subject' => $donnees['sujet'],
                'email_html' => View::make('esbtp.emails.acces-rendez-vous', $donnees)->render(),
            ],
        ]);
    }

    public static function lien(string $jeton): string
    {
        $ecole = strtolower(trim((string) config('app.tenant_code', '')));

        return config('verification_contact.url_portail_public').'/verification-email?ecole='.rawurlencode($ecole)
            .'#jeton='.rawurlencode($jeton);
    }

    /** @return array<string, mixed> */
    private function donnees(string $code, string $jeton): array
    {
        $ecole = SettingsHelper::getSchoolInfo();
        $pdf = SettingsHelper::getPdfSettings();
        $identite = $this->identite->decrire();
        $nom = trim((string) ($ecole['name'] ?? '')) ?: (trim((string) ($identite['nom'] ?? '')) ?: 'votre établissement');
        $logo = $identite['logo']['url'] ?? null;

        return [
            'sujet' => 'Confirmez votre adresse e-mail · '.$nom,
            'code' => $code,
            'lien' => self::lien($jeton),
            'minutes' => (int) config('verification_contact.code_expire_minutes', 30),
            'heures' => (int) config('verification_contact.lien_expire_heures', 48),
            'schoolName' => $nom,
            'schoolLogoUrl' => is_string($logo) ? $logo : null,
            'emailPrimaryColor' => $pdf['primary_color'] ?? '#0453cb',
        ];
    }
}
