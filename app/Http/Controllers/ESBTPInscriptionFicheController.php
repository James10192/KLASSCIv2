<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPInscription;
use App\Services\Photos\StockagePhoto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ESBTPInscriptionFicheController extends Controller
{
    use Concerns\RespondsWithInlinePdf;

    /**
     * Au-dela, la photo n'est plus embarquee.
     *
     * Le document part en base64 dans le HTML que DomPDF avale, et il en porte
     * DEUX exemplaires : une photo d'appareil non redimensionnee gonflerait la
     * fiche de plusieurs megaoctets et ferait expirer le rendu. La fiche sans
     * photo reste imprimable, avec son cadre a coller ; une fiche qui ne sort
     * pas ne l'est pas.
     */
    private const PHOTO_OCTETS_MAX = 1_500_000;

    public function preview(Request $request, ESBTPInscription $inscription, StockagePhoto $photos)
    {
        $inscription->load(['etudiant', 'classe.filiere', 'classe.niveau', 'filiere', 'niveau', 'anneeUniversitaire']);
        $school = SettingsHelper::getSchoolInfo();
        $photo = $this->photoEmbarquee($inscription, $photos);

        $pdf = Pdf::loadView('esbtp.inscriptions.pdf.fiche-double', compact('inscription', 'school', 'photo'))
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

    /**
     * La photo de l'etudiant en donnee embarquee, ou null.
     *
     * `isRemoteEnabled` est a false, et c'est voulu : DomPDF n'ira chercher
     * aucune URL. La photo doit donc voyager DANS le document, ou pas du tout.
     */
    private function photoEmbarquee(ESBTPInscription $inscription, StockagePhoto $photos): ?string
    {
        $chemin = $photos->chemin($inscription->etudiant?->photo);

        if ($chemin === null) {
            return null;
        }

        $absolu = Storage::disk('public')->path($chemin);
        $taille = @filesize($absolu);

        if ($taille === false || $taille > self::PHOTO_OCTETS_MAX) {
            return null;
        }

        $binaire = @file_get_contents($absolu);

        if ($binaire === false) {
            return null;
        }

        // Le type vient du fichier lui-meme, pas de son extension : une photo
        // renommee a la main donnerait un type faux, et l'image ne s'afficherait
        // pas sans que rien ne le dise.
        $type = @mime_content_type($absolu);

        if (! is_string($type) || ! str_starts_with($type, 'image/')) {
            return null;
        }

        return 'data:'.$type.';base64,'.base64_encode($binaire);
    }
}
