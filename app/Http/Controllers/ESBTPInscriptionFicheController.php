<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPInscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ESBTPInscriptionFicheController extends Controller
{
    use Concerns\RespondsWithInlinePdf;

    public function preview(Request $request, ESBTPInscription $inscription)
    {
        $inscription->load(['etudiant', 'classe.filiere', 'classe.niveau', 'filiere', 'niveau', 'anneeUniversitaire']);
        $school = SettingsHelper::getSchoolInfo();
        $pdf = Pdf::loadView('esbtp.inscriptions.pdf.fiche-double', compact('inscription', 'school'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
            ]);
        $filename = 'fiche-inscription-'.Str::slug($inscription->etudiant?->nom_complet ?? 'inscription').'.pdf';

        return $this->respondWithPdf($pdf, $filename, $request);
    }
}
