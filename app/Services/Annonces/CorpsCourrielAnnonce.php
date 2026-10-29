<?php
namespace App\Services\Annonces;

use App\Mail\Transport\CorpsPourMailPulse;
use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\View;

final class CorpsCourrielAnnonce
{
    public function html(ESBTPAnnonce $annonce): string
    {
        return CorpsPourMailPulse::resserrer(View::make('esbtp.emails.annonce', compact('annonce'))->render());
    }
    public function texte(ESBTPAnnonce $annonce): string
    {
        $contenu = trim(html_entity_decode(strip_tags((string) $annonce->contenu), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return $annonce->titre."\n\n".$contenu."\n\nConsultez votre espace KLASSCI : ".route('esbtp.mes-annonces.index');
    }
}
