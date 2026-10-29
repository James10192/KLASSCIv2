<?php

namespace App\Services\Annonces;

use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\View;

final class ApercuCourrielAnnonce
{
    /**
     * Ne pas accepter la publication si MailPulse rejetterait le gabarit HTML.
     * Reserve une marge pour le reste des metadonnees (expediteur, tenant, sujet).
     */
    public function verifierTaille(string $titre, string $contenu, int $priorite = 0): void
    {
        $html = \App\Mail\Transport\CorpsPourMailPulse::resserrer(
            $this->htmlAvantPublication($titre, $contenu, $priorite)
        );
        $reserve = (int) config('services.mailpulse.announcement_metadata_budget_bytes', 14000);
        $budget = max(1024, min(15000, $reserve));
        $taille = \App\Mail\Transport\CorpsPourMailPulse::octetsJson(['email_html' => $html]);
        if ($taille > $budget) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'contenu' => "Ce communiqué est trop long pour le format e-mail. Raccourcissez-le avant de le publier avec MailPulse.",
            ]);
        }
    }

    public function htmlAvantPublication(string $titre, string $contenu, int $priorite = 0): string
    {
        $annonce = new ESBTPAnnonce();
        $annonce->titre = $titre;
        $annonce->contenu = $contenu;
        $annonce->priorite = $priorite;
        return $this->html($annonce);
    }

    public function html(ESBTPAnnonce $annonce): string
    {
        return View::make('esbtp.emails.annonce-etudiants', [
            'annonce' => $annonce,
            'texte' => trim(html_entity_decode(strip_tags((string) $annonce->contenu), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'lien' => route('esbtp.mes-annonces.index'),
        ])->render();
    }
}
