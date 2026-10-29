<?php

namespace App\Services\Annonces;

use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\View;

final class ApercuCourrielAnnonce
{
    public function html(ESBTPAnnonce $annonce): string
    {
        return View::make('esbtp.emails.annonce-etudiants', [
            'annonce' => $annonce,
            'texte' => trim(html_entity_decode(strip_tags((string) $annonce->contenu), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'lien' => route('esbtp.mes-annonces.index'),
        ])->render();
    }
}
