<?php

namespace App\Http\Controllers\Concerns;

use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait RespondsWithInlinePdf
{
    /**
     * Renvoie le PDF en download par défaut, ou inline si `?inline=1`.
     * Utilisé par les boutons "Imprimer" qui ouvrent le PDF dans un nouvel onglet
     * pour que l'utilisateur déclenche l'impression depuis le viewer du navigateur.
     */
    protected function respondWithPdf(PDF $pdf, string $filename, ?Request $request = null): Response|\Symfony\Component\HttpFoundation\Response
    {
        $request = $request ?? request();

        if ($request && $request->boolean('inline')) {
            return new Response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        }

        return $pdf->download($filename);
    }
}
