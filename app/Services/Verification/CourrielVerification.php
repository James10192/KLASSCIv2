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
