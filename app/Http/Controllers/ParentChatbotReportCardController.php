<?php

namespace App\Http\Controllers;

use App\Models\ESBTPBulletin;
use App\Services\ParentChatbot\ParentChatbotPublicationPolicy;
use Symfony\Component\HttpFoundation\Response;

class ParentChatbotReportCardController extends Controller
{
    public function __invoke(ESBTPBulletin $bulletin, ParentChatbotPublicationPolicy $publicationPolicy): Response
    {
        abort_unless($publicationPolicy->reportCardIsPublished($bulletin), 404);

        return response()
            ->make('Ce lien de bulletin n\'est plus disponible. Demandez BULLETIN sur WhatsApp pour recevoir le resume publie.', 410)
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
