<?php

namespace App\Http\Controllers\Concerns;

use App\Domain\Exploitation\TracesLentes\EnregistreurDeTraces;
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
        // Le nom de la route, jamais celui du fichier : il porte souvent le nom d'un élève.
        $nom = 'export.pdf:' . ($request?->route()?->getName() ?? 'sans-route');

        return app(EnregistreurDeTraces::class)->mesurer(EnregistreurDeTraces::TRAVAIL, $nom, function () use ($pdf, $filename, $request) {
            if ($request && $request->boolean('inline')) {
                return new Response($pdf->output(), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
                ]);
            }

            return $pdf->download($filename);
        });
    }
}
