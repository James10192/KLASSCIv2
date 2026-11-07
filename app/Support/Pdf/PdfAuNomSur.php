<?php

namespace App\Support\Pdf;

use App\Support\NomDeFichier;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Response;

/**
 * Le PDF de DomPDF, dont le nom de fichier est nettoyé avant l'en-tête.
 *
 * Branché sur « dompdf.wrapper » : tous les Pdf::loadView() de l'application
 * en profitent, sans toucher aux quarante appelants qui composent un nom à
 * partir d'un matricule, d'une classe ou d'une année.
 */
class PdfAuNomSur extends PDF
{
    public function download(string $filename = 'document.pdf'): Response
    {
        return parent::download(NomDeFichier::sur($filename, 'document.pdf'));
    }

    public function stream(string $filename = 'document.pdf'): Response
    {
        return parent::stream(NomDeFichier::sur($filename, 'document.pdf'));
    }
}
