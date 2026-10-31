<?php

namespace App\Services\Annonces;

use App\Mail\Transport\CorpsPourMailPulse;
use App\Mail\Transport\MailPulseTransport;
use App\Models\ESBTPAnnonce;
use App\Services\Vitrine\IdentitePublique;
use Illuminate\Support\Facades\View;

/**
 * Un seul rendu pour la prévisualisation KLASSCI et la remise via MailPulse.
 * Les annonces sont du texte brut : aucun HTML utilisateur n'est exécuté.
 */
final class CourrielAnnonce
{
    public function __construct(private readonly IdentitePublique $identite)
    {
    }

    public function html(ESBTPAnnonce $annonce): string
    {
        $identite = $this->identite->decrire();
        $infos = \App\Helpers\SettingsHelper::getSchoolInfo();
        $pdf = \App\Helpers\SettingsHelper::getPdfSettings();

        $donnees = [
            'annonce' => $annonce,
            'lien' => route('esbtp.mes-annonces.index'),
            'schoolName' => trim((string) ($infos['name'] ?? $identite['nom'] ?? 'KLASSCI')),
            'schoolLogoUrl' => $identite['logo']['url'] ?? null,
            'emailPrimaryColor' => $pdf['primary_color'] ?? '#0453cb',
        ];

        return CorpsPourMailPulse::resserrer(View::make('esbtp.emails.annonce', $donnees)->render());
    }

    public function htmlPourMailPulse(ESBTPAnnonce $annonce, array $metadonnees): ?string
    {
        $html = $this->html($annonce);
        if (CorpsPourMailPulse::octetsJson($metadonnees + ['email_html' => $html])
            > MailPulseTransport::PLAFOND_METADATA - 512) {
            return null;
        }

        return $html;
    }
}
