<?php

namespace App\Services\RendezVous;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPRdvReservation;
use Barryvdh\DomPDF\Facade\Pdf;

class ConvocationRdvPdf
{
    public function jeton(ESBTPRdvReservation $reservation): string
    {
        return $reservation->id.'.'.hash_hmac('sha256', 'rdv-pdf:'.$reservation->id, (string) config('app.key'));
    }

    public function url(ESBTPRdvReservation $reservation): string
    {
        return rtrim((string) config('app.url'), '/').'/convocation-rdv/'.$this->jeton($reservation);
    }

    public function depuisJeton(string $jeton): ?ESBTPRdvReservation
    {
        $parties = explode('.', $jeton, 2);
        if (count($parties) !== 2 || ! ctype_digit($parties[0])) {
            return null;
        }
        $id = (int) $parties[0];
        $attendu = hash_hmac('sha256', 'rdv-pdf:'.$id, (string) config('app.key'));
        if (! hash_equals($attendu, $parties[1])) {
            return null;
        }

        return ESBTPRdvReservation::query()->with('creneau')->find($id);
    }

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
