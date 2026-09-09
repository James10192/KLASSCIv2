<?php

namespace App\Services\RendezVous;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPRdvReservation;
use Barryvdh\DomPDF\Facade\Pdf;

class ConvocationRdvPdf
{
    public function binaire(ESBTPRdvReservation $reservation): string
    {
        $settings = SettingsHelper::getPdfSettings();
        $logo = SettingsHelper::resolveLogoBase64();
        $ecole = SettingsHelper::getSchoolInfo();
        $reservation->loadMissing(['creneau', 'candidature', 'demande']);
        $creneau = $reservation->creneau;

        return Pdf::loadView('esbtp.rendez-vous.pdf.convocation', [
            'reservation' => $reservation,
            'creneau' => $creneau,
            'reference' => $reservation->porteur()?->referencePubliqueAffichee(),
            'settings' => $settings,
            'logo' => $logo,
            'ecole' => $ecole,
            'isPdfExport' => true,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'isFontSubsettingEnabled' => true,
            ])
            ->output();
    }
}
