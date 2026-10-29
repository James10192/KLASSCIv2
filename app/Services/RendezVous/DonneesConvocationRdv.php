<?php

namespace App\Services\RendezVous;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPRdvReservation;
use App\Services\Vitrine\IdentitePublique;

/**
 * Construit une seule fois les donnees de convocation partagees par l'e-mail
 * et WhatsApp. Les deux canaux doivent annoncer exactement le meme rendez-vous
 * et pointer vers la meme convocation PDF.
 */
class DonneesConvocationRdv
{
    public function __construct(
        private readonly IdentitePublique $identite,
        private readonly ConvocationRdvPdf $pdf,
        private readonly RendezVousReglages $reglages,
    ) {}

    /** @return array<string, mixed> */
    public function pour(ESBTPRdvReservation $reservation, string $action): array
    {
        $creneau = $reservation->creneau;
        $ecole = SettingsHelper::getSchoolInfo();
        $pdf = SettingsHelper::getPdfSettings();

        $porteur = $reservation->porteur();
        $porteur?->assurerReferencePublique();
        $reference = $porteur?->referencePubliqueAffichee() ?? '';
        $nomEcole = trim((string) ($ecole['name'] ?? '')) ?: $this->nomEcole();
        $logo = $this->identite->decrire()['logo']['url'] ?? null;

        $intro = match ($action) {
            'deplace' => 'Votre rendez-vous a été déplacé.',
            'annule' => 'Votre rendez-vous a été annulé.',
            default => 'Votre rendez-vous est confirmé.',
        };

        return [
            'sujet' => $intro.' — '.$nomEcole,
            'intro' => $intro,
            'nom' => trim($reservation->nom.' '.$reservation->prenoms),
            'date' => $creneau?->date?->translatedFormat('l j F Y') ?? '—',
            'heure' => $creneau ? ($creneau->heureDebutHi().' – '.$creneau->heureFinHi()) : '—',
            'reference' => $reference,
            'lieu' => $this->reglages->lieu(),
            'lien' => $this->lienReservation($reference),
            'lienPdf' => $action === 'annule' ? '' : $this->pdf->url($reservation),
            'schoolName' => $nomEcole,
            'schoolLogoUrl' => is_string($logo) ? $logo : null,
            'emailPrimaryColor' => $pdf['primary_color'] ?? '#0453cb',
            'emailHeaderBgColor' => $pdf['header_bg_color'] ?? ($pdf['primary_color'] ?? '#0453cb'),
            'emailHeaderTextColor' => $pdf['header_text_on_bg'] ?? ($pdf['header_text_color'] ?? '#ffffff'),
        ];
    }

    private function nomEcole(): string
    {
        $nom = trim((string) ($this->identite->decrire()['nom'] ?? ''));

        return $nom !== '' ? $nom : 'votre établissement';
    }

    private function lienReservation(string $referenceAffichee): string
    {
        $code = strtolower(trim((string) config('app.tenant_code', '')));

        return 'https://www.klassci.com/inscription/universite/'.$code.'/rendez-vous?ref='
            .rawurlencode($referenceAffichee);
    }
}
