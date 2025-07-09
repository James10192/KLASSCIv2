<?php

namespace App\Services;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPDepense;
use App\Models\ESBTPBonSortie;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;

class PDFService
{
    /**
     * Génère un PDF de bon de sortie
     */
    public function genererPDFBonSortie($bonSortie)
    {
        try {
            $data = [
                'bon' => $bonSortie,
                'date_generation' => Carbon::now()->format('d/m/Y H:i'),
                'numero_bon' => $bonSortie->numero_bon ?? 'BON-' . date('Ymd') . '-' . $bonSortie->id
            ];

            $html = View::make('esbtp.comptabilite.bons-sortie.pdf', $data)->render();
            
            // Simulation de génération PDF (remplacer par dompdf)
            $pdfPath = storage_path('app/public/bons/' . $data['numero_bon'] . '.pdf');
            
            // Ici vous utiliseriez dompdf:
            // $pdf = \PDF::loadHTML($html);
            // $pdf->save($pdfPath);
            
            return [
                'success' => true,
                'path' => $pdfPath,
                'filename' => $data['numero_bon'] . '.pdf'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur génération PDF: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Génère un reçu de paiement
     */
    public function genererRecuPaiement($paiement)
    {
        try {
            $data = [
                'paiement' => $paiement,
                'etudiant' => $paiement->etudiant,
                'date_generation' => Carbon::now()->format('d/m/Y H:i'),
                'numero_recu' => 'RECU-' . $paiement->id . '-' . date('Ymd')
            ];

            $html = View::make('esbtp.comptabilite.paiements.recu-pdf', $data)->render();
            
            $pdfPath = storage_path('app/public/recus/' . $data['numero_recu'] . '.pdf');
            
            // Génération PDF avec dompdf
            // $pdf = \PDF::loadHTML($html);
            // $pdf->save($pdfPath);
            
            return [
                'success' => true,
                'path' => $pdfPath,
                'filename' => $data['numero_recu'] . '.pdf'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur génération reçu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Génère un rapport financier
     */
    public function genererRapportFinancier($donnees)
    {
        try {
            $data = array_merge($donnees, [
                'date_generation' => Carbon::now()->format('d/m/Y H:i'),
                'titre' => 'Rapport Financier - ' . Carbon::now()->format('F Y')
            ]);

            $html = View::make('esbtp.comptabilite.rapports.rapport-pdf', $data)->render();
            
            $filename = 'rapport_financier_' . date('Ymd_His') . '.pdf';
            $pdfPath = storage_path('app/public/rapports/' . $filename);
            
            // Génération PDF
            // $pdf = \PDF::loadHTML($html);
            // $pdf->save($pdfPath);
            
            return [
                'success' => true,
                'path' => $pdfPath,
                'filename' => $filename
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur génération rapport: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Génère un bulletin de salaire
     */
    public function genererBulletinSalaire($salaire)
    {
        try {
            $moisNoms = [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
            ];

            $data = [
                'salaire' => $salaire,
                'employe' => $salaire->user,
                'mois_nom' => $moisNoms[$salaire->mois],
                'annee' => $salaire->annee,
                'date_generation' => Carbon::now()->format('d/m/Y H:i')
            ];

            $html = View::make('esbtp.comptabilite.salaires.bulletin-pdf', $data)->render();
            
            $filename = 'bulletin_' . $salaire->user->name . '_' . $salaire->mois . '_' . $salaire->annee . '.pdf';
            $pdfPath = storage_path('app/public/bulletins/' . $filename);
            
            return [
                'success' => true,
                'path' => $pdfPath,
                'filename' => $filename
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur génération bulletin: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crée les dossiers nécessaires pour les PDFs
     */
    public function creerDossiersPDF()
    {
        $dossiers = [
            'public/bons',
            'public/recus', 
            'public/rapports',
            'public/bulletins'
        ];

        foreach ($dossiers as $dossier) {
            $path = storage_path('app/' . $dossier);
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
}
