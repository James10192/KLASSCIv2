<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Services\RendezVous\ConvocationRdvPdf;
use Symfony\Component\HttpFoundation\Response;

class ConvocationRdvPdfController extends Controller
{
    public function __invoke(string $jeton, ConvocationRdvPdf $pdf): Response
    {
        $reservation = $pdf->depuisJeton($jeton);
        abort_if($reservation === null, 404);

        return response($pdf->binaire($reservation), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="convocation-guichet.pdf"',
        ]);
    }
}
