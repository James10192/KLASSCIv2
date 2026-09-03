<?php

namespace App\Domain\OfficialDocuments\Services;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Rendu PDF du releve de notes, a partir du seul instantane.
 *
 * Memes options que JuryPvRenderer : pas de ressource distante, pas de PHP dans
 * le gabarit. Un document officiel ne doit dependre d'aucun reseau au moment ou
 * on le regenere.
 */
class LmdTranscriptRenderer
{
    public function render(array $snapshot, string $verificationCode): string
    {
        return Pdf::loadView('pdf.lmd-releve-notes', compact('snapshot', 'verificationCode'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'isFontSubsettingEnabled' => true,
            ])->output();
    }
}
