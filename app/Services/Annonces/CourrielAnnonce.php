<?php

namespace App\Services\Annonces;

use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\View;

/**
 * Une seule source de contenu pour l'envoi MailPulse et l'aperçu KLASSCI.
 * Reutilise le gabarit d'e-mails de l'etablissement (logo, couleurs, contact).
 */
final class CourrielAnnonce
{
    public function sujet(ESBTPAnnonce $annonce): string
    {
        return 'Annonce - '.$annonce->titre;
    }

    public function texte(ESBTPAnnonce $annonce): string
    {
        $contenu = trim(html_entity_decode(strip_tags((string) $annonce->contenu), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $annonce->titre."\n\n".$contenu
            ."\n\nConsultez cette annonce depuis votre espace KLASSCI : "
            .route('esbtp.mes-annonces.index');
    }

    public function html(ESBTPAnnonce $annonce): string
    {
        return View::make('esbtp.emails.annonce', [
            'annonce' => $annonce,
            'urlAnnonce' => route('esbtp.mes-annonces.index'),
            'corpsAnnonce' => trim(html_entity_decode(strip_tags((string) $annonce->contenu), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
        ])->render();
    }
}
