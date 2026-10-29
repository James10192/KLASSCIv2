<?php

namespace App\Services\Annonces;

use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\View;

final class ApercuCourrielAnnonce
{
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
