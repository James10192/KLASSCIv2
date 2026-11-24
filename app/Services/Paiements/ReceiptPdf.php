<?php

namespace App\Services\Paiements;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Support\ReceiptPrintSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

final class ReceiptPdf
{
    public function render(array $data, array $print)
    {
        $pdf = Pdf::loadView('esbtp.paiements.recu', array_merge($data, ['receiptPrint' => $print]))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'isFontSubsettingEnabled' => true,
            ]);
        $pdf->render();

        return $pdf;
    }

    public function pages($pdf): int
    {
        return $pdf->getDomPDF()->getCanvas()->get_page_count();
    }

    public function forPayment(array $data)
    {
        $settings = ReceiptPrintSettings::read();
        $pdf = $this->render($data, $settings);
        if ($this->pages($pdf) === 1) {
            return $pdf;
        }
        $pdf = $this->render($data, ReceiptPrintSettings::compact($settings));
        if ($this->pages($pdf) === 1) {
            return $pdf;
        }
        $pdf = $this->render($data, ReceiptPrintSettings::compact(ReceiptPrintSettings::defaults()));
        abort_if($this->pages($pdf) !== 1, 422, 'Ce reçu ne tient pas sur un A4 avec deux exemplaires lisibles. Contactez le service technique.');

        return $pdf;
    }

    public function sample(array $settings): array
    {
        $etudiant = new ESBTPEtudiant;
        $etudiant->matricule = 'DEMO-0001';
        $etudiant->nom = 'EXEMPLE';
        $etudiant->prenoms = 'ETUDIANT FICTIF';
        $user = new User;
        $user->name = 'ETUDIANT FICTIF POUR APERCU';
        $etudiant->setRelation('user', $user);

        $filiere = new ESBTPFiliere;
        $filiere->name = 'RESSOURCES HUMAINES ET COMMUNICATION';
        $niveau = new ESBTPNiveauEtude;
        $niveau->name = 'BTS deuxième année';
        $annee = new ESBTPAnneeUniversitaire;
        $annee->name = '2026-2027';

        $inscription = new ESBTPInscription;
        $inscription->affectation_status = 'affecté';
        $inscription->setRelation('filiere', $filiere);
        $inscription->setRelation('niveauEtude', $niveau);
        $inscription->setRelation('anneeUniversitaire', $annee);

        $emetteur = new User;
        $emetteur->name = 'CAISSE DEMONSTRATION';

        $paiement = new ESBTPPaiement;
        $paiement->numero_recu = 'DEMO-2026-0008';
        $paiement->montant = 60000;
        $paiement->date_paiement = Carbon::parse('2026-10-22');
        $paiement->created_at = Carbon::parse('2026-10-22 11:21:00');
        $paiement->date_validation = Carbon::parse('2026-10-22');
        $paiement->validation_recorded_at = Carbon::parse('2026-10-22 11:21:00');
        $paiement->reference_paiement = 'REF-CAISSE-2026-0008';
        $paiement->status = 'validé';
        $paiement->mode_paiement = 'Espèces';
        $paiement->tranche = 'Première tranche';
        $paiement->setRelation('etudiant', $etudiant);
        $paiement->setRelation('inscription', $inscription);
        $paiement->setRelation('fraisCategory', null);
        $paiement->setRelation('creator', $emetteur);
        $paiement->setRelation('allocations', collect());

        $avant = new ESBTPPaiement;
        $avant->date_paiement = Carbon::parse('2026-09-01');
        $avant->montant = 40000;
        $avant->numero_recu = 'DEMO-2026-0007';

        $avantSecond = new ESBTPPaiement;
        $avantSecond->date_paiement = Carbon::parse('2026-10-21');
        $avantSecond->montant = 123000;
        $avantSecond->numero_recu = 'DEMO-2026-0006';

        $frais = collect([
            ['name' => 'FRAIS D\'iNSCRIPTION', 'checked' => true, 'in_kind' => false, 'restant' => 0, 'current' => true, 'non_configure' => false],
            ['name' => 'FRAIS DE TENUE', 'checked' => true, 'in_kind' => false, 'restant' => 0, 'current' => false, 'non_configure' => false],
            ['name' => 'SCOLARITE', 'checked' => false, 'in_kind' => false, 'restant' => 350000, 'current' => false, 'non_configure' => false],
            ['name' => 'PAQUET DE RAM', 'checked' => false, 'in_kind' => true, 'restant' => 0, 'current' => false, 'non_configure' => false],
            ['name' => 'CHEMISE CARTONNEE', 'checked' => false, 'in_kind' => true, 'restant' => 0, 'current' => false, 'non_configure' => false],
            ['name' => 'ASSURANCE', 'checked' => false, 'in_kind' => false, 'restant' => 0, 'current' => false, 'non_configure' => true],
        ]);

        return [
            'paiement' => $paiement,
            'settings' => $settings,
            'fraisLignes' => $frais,
            'resteAPayer' => 365000,
            'totalVerse' => 100000,
            'versementsAvant' => collect([$avantSecond, $avant]),
            'versementsApres' => collect(),
            'affectationLabel' => 'Affecté',
        ];
    }
}
