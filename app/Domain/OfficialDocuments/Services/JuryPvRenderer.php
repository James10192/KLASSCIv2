<?php

namespace App\Domain\OfficialDocuments\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class JuryPvRenderer
{
    public function render(array $snapshot, string $verificationCode): string
    {
        return Pdf::loadView('pdf.lmd-jury-pv', compact('snapshot', 'verificationCode'))
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
