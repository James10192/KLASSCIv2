<?php

namespace App\Services\RendezVous;

use App\Contracts\PorteurDeRendezVous;
use App\Helpers\SettingsHelper;
use App\Jobs\EnvoyerConvocationRdvJob;
use App\Jobs\EnvoyerMailRdvJob;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPRdvReservation;
use App\Services\Vitrine\IdentitePublique;
use Illuminate\Support\Facades\View;

class MessagerieRdv
{
    public function __construct(
        private readonly IdentitePublique $identite,
        private readonly ConvocationRdvPdf $pdf,
    ) {
    }

    public function confirmer(ESBTPRdvReservation $reservation, string $action = 'confirme'): void
    {
        EnvoyerConvocationRdvJob::dispatch($reservation->id, $action);
    }

    public function expedierConvocation(ESBTPRdvReservation $reservation, string $action = 'confirme'): void
    {
        $email = trim((string) ($reservation->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $creneau = $reservation->creneau;
        $ecole = $this->nomEcole();
        $porteur = $reservation->porteur();
        $reference = $porteur?->referencePubliqueAffichee() ?? '';
        $date = $creneau?->date?->translatedFormat('l j F Y') ?? '—';
        $heure = $creneau ? ($creneau->heureDebutHi().' – '.$creneau->heureFinHi()) : '—';
        $lien = $this->lienReservation($reference);

        $intro = match ($action) {
            'deplace' => 'Votre rendez-vous a été déplacé.',
            'annule' => 'Votre rendez-vous a été annulé.',
            default => 'Votre rendez-vous est confirmé.',
        };

        $texte = $intro."\n\n".$date.' '.$heure."\nRéférence : ".$reference."\n".$lien;
        $html = $this->htmlConvocation($reservation, $date, $heure, $reference, $lien, $intro);
        $pdf = $action === 'annule' ? null : base64_encode($this->pdf->binaire($reservation));

        EnvoyerMailRdvJob::dispatch($email, $intro.' — '.$ecole, $texte, $html, $pdf);
    }

    /**
     * @return array{envoyes: int, sans_email: int, deja: int}
     */
    public function inviterEnAttente(bool $ecrire = false): array
    {
        $rapport = ['envoyes' => 0, 'sans_email' => 0, 'deja' => 0];
        $ecole = $this->nomEcole();

        ESBTPCandidature::query()
            ->where('statut', ESBTPCandidature::STATUT_EN_ATTENTE)
            ->whereDoesntHave('reservations', fn ($q) => $q->occupantes())
            ->orderBy('id')
            ->each(function (ESBTPCandidature $c) use ($ecrire, $ecole, &$rapport) {
                $this->inviterPorteur($c, $ecrire, $ecole, $rapport);
            });

        ESBTPReinscriptionDemande::query()
            ->where('statut', ESBTPReinscriptionDemande::STATUT_EN_ATTENTE)
            ->whereDoesntHave('reservations', fn ($q) => $q->occupantes())
            ->with('etudiant')
            ->orderBy('id')
            ->each(function (ESBTPReinscriptionDemande $d) use ($ecrire, $ecole, &$rapport) {
                $this->inviterPorteur($d, $ecrire, $ecole, $rapport);
            });

        return $rapport;
    }

    /**
     * @param  array{envoyes: int, sans_email: int, deja: int}  $rapport
     */
    private function inviterPorteur(PorteurDeRendezVous $porteur, bool $ecrire, string $ecole, array &$rapport): void
    {
        if ($porteur->dejaInviteRdv()) {
            $rapport['deja']++;

            return;
        }

        $email = trim((string) $porteur->emailRdv());
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $rapport['sans_email']++;

            return;
        }

        if (! $ecrire) {
            $rapport['envoyes']++;

            return;
        }

        $prenom = $porteur->prenomRdv();
        $reference = $porteur->referencePubliqueAffichee() ?? '';
        $texte = "Bonjour {$prenom},\n\nPrenez rendez-vous au guichet de {$ecole}.\nRéférence : {$reference}\n".$this->lienReservation($reference);

        $porteur->marquerInviteRdv();
        EnvoyerMailRdvJob::dispatch($email, 'Prenez rendez-vous — '.$ecole, $texte);
        $rapport['envoyes']++;
    }

    private function htmlConvocation(
        ESBTPRdvReservation $reservation,
        string $date,
        string $heure,
        string $reference,
        string $lien,
        string $intro,
    ): string {
        $ecole = SettingsHelper::getSchoolInfo();
        $pdf = SettingsHelper::getPdfSettings();
        $logo = SettingsHelper::resolveLogoBase64();

        return View::make('esbtp.emails.parents.rendez-vous-convocation', [
            'parentName' => $reservation->prenoms ?: $reservation->nom,
            'studentName' => trim($reservation->prenoms.' '.$reservation->nom),
            'nom' => trim($reservation->nom.' '.$reservation->prenoms),
            'date' => $date,
            'heure' => $heure,
            'reference' => $reference,
            'lien' => $lien,
            'intro' => $intro,
            'schoolName' => $ecole['name'] ?? 'KLASSCI',
            'schoolAddress' => $ecole['address'] ?? '',
            'schoolPhone' => $ecole['phone'] ?? '',
            'schoolEmail' => $ecole['email'] ?? '',
            'schoolLogoPath' => $logo ? 'logo' : null,
            'emailPrimaryColor' => $pdf['primary_color'] ?? '#0453cb',
            'emailHeaderBgColor' => $pdf['header_bg_color'] ?? ($pdf['primary_color'] ?? '#0453cb'),
            'emailHeaderTextColor' => $pdf['header_text_on_bg'] ?? ($pdf['header_text_color'] ?? '#ffffff'),
            'emailSecondaryColor' => $pdf['secondary_color'] ?? '#64748b',
            'message' => new class($logo) {
                public function __construct(private readonly ?array $logo)
                {
                }

                public function embed(string $path): string
                {
                    return $this->logo['data_uri'] ?? $path;
                }
            },
        ])->render();
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
