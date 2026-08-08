<?php

namespace App\Services\ParentChatbot;

use App\Http\Controllers\ESBTPBulletinController;
use App\Models\ESBTPBulletin;

/**
 * Renders the official report card PDF for the parent chatbot rail.
 *
 * The rendering itself is delegated to the canonical builder already used by
 * the staff UI and by the bulk export, so a parent downloads exactly the same
 * document the school produces. Nothing is persisted: this runs on a GET.
 */
class ParentChatbotReportCardPdfRenderer
{
    /** @return string Raw PDF bytes. */
    public function render(ESBTPBulletin $bulletin): string
    {
        return app(ESBTPBulletinController::class)
            ->buildBulletinPdf($bulletin, false)
            ->output();
    }
}
