<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

class PaiementsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize, WithEvents
{
    protected $paiements;
    protected $stats;
    protected $filters;

    public function __construct(Collection $paiements, array $stats = [], array $filters = [])
    {
        $this->paiements = $paiements;
        $this->stats = $stats;
        $this->filters = $filters;
    }

    /**
     * Collection des paiements à exporter
     */
    public function collection()
    {
        return $this->paiements;
    }

    /**
     * En-têtes des colonnes
     */
    public function headings(): array
    {
        return [
            'N°',
            'Date paiement',
            'Matricule',
            'Nom étudiant',
            'Prénoms',
            'Classe',
            'Filière',
            'Niveau',
            'Catégorie de frais',
            'Montant (FCFA)',
            'Mode de paiement',
            'Statut',
            'N° reçu',
            'Validé par',
            'Date validation',
            'Commentaire',
            'Année universitaire'
        ];
    }

    /**
     * Mapper chaque paiement vers une ligne
     */
    public function map($paiement): array
    {
        static $index = 0;
        $index++;

        $etudiant = $paiement->etudiant;
        $inscription = $paiement->inscription;
        $fraisCategory = $paiement->fraisCategory;
        $categorie = $paiement->categorie; // Ancien système (fallback)

        return [
            $index,
            $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : 'N/A',
            $etudiant ? $etudiant->matricule : 'N/A',
            $etudiant ? $etudiant->nom : '',
            $etudiant ? $etudiant->prenoms : '',
            $inscription && $inscription->classe ? $inscription->classe->nom : 'N/A',
            $inscription && $inscription->filiere ? $inscription->filiere->name : 'N/A',
            $inscription && $inscription->niveauEtude ? $inscription->niveauEtude->name : 'N/A',
            $fraisCategory ? $fraisCategory->name : ($categorie ? $categorie->nom : $paiement->motif ?? 'N/A'),
            $paiement->montant ?? 0,
            $paiement->mode_paiement ?? 'N/A',
            $this->getStatutLabel($paiement->status),
            $paiement->numero_recu ?? 'N/A',
            $paiement->validatedBy ? $paiement->validatedBy->name : 'N/A',
            $paiement->date_validation ? $paiement->date_validation->format('d/m/Y H:i') : 'N/A',
            $paiement->commentaire ?? '',
            $inscription && $inscription->anneeUniversitaire ? $inscription->anneeUniversitaire->name : 'N/A',
        ];
    }

    /**
     * Titre de la feuille Excel
     */
    public function title(): string
    {
        return 'Liste Paiements';
    }

    /**
     * Styles pour la feuille Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style pour l'en-tête
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => '0453cb', // Bleu KLASSCI
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Événements pour ajouter des totaux et informations
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Geler la première ligne (en-têtes)
                $sheet->freezePane('A2');

                // Formater la colonne des montants avec le format monétaire
                $sheet->getStyle("J2:J{$highestRow}")
                      ->getNumberFormat()
                      ->setFormatCode('#,##0 "FCFA"');

                // Ajouter une bordure légère sur toutes les cellules de données
                $sheet->getStyle("A1:Q{$highestRow}")
                      ->getBorders()
                      ->getAllBorders()
                      ->setBorderStyle(Border::BORDER_THIN)
                      ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CCCCCC'));

                // Ajouter les statistiques en bas
                if (!empty($this->stats)) {
                    $this->addStatistics($sheet, $highestRow);
                }

                // Ajouter les filtres appliqués en bas
                if (!empty($this->filters)) {
                    $this->addFiltersInfo($sheet, $highestRow);
                }
            }
        ];
    }

    /**
     * Ajouter les statistiques en bas de la feuille
     */
    private function addStatistics($sheet, $highestRow)
    {
        $statsRow = $highestRow + 3;

        // Titre
        $sheet->setCellValue("A{$statsRow}", "STATISTIQUES");
        $sheet->getStyle("A{$statsRow}")->getFont()->setBold(true)->setSize(12);
        $sheet->mergeCells("A{$statsRow}:D{$statsRow}");
        $statsRow++;

        // Total des paiements
        $totalPaiements = $this->stats['total'] ?? 0;
        $sheet->setCellValue("A{$statsRow}", "Nombre total de paiements:");
        $sheet->setCellValue("B{$statsRow}", $totalPaiements);
        $sheet->getStyle("A{$statsRow}")->getFont()->setBold(true);
        $statsRow++;

        // Montant total
        $montantTotal = $this->stats['montant_total'] ?? 0;
        $sheet->setCellValue("A{$statsRow}", "Montant total:");
        $sheet->setCellValue("B{$statsRow}", $montantTotal);
        $sheet->getStyle("B{$statsRow}")->getNumberFormat()->setFormatCode('#,##0 "FCFA"');
        $sheet->getStyle("A{$statsRow}")->getFont()->setBold(true);
        $statsRow++;

        // Paiements validés
        $valides = $this->stats['valides'] ?? 0;
        $montantValide = $this->stats['montant_valide'] ?? 0;
        $sheet->setCellValue("A{$statsRow}", "Paiements validés:");
        $sheet->setCellValue("B{$statsRow}", "{$valides} ({$this->formatMontant($montantValide)})");
        $statsRow++;

        // Paiements en attente
        $enAttente = $this->stats['en_attente'] ?? 0;
        $montantEnAttente = $this->stats['montant_en_attente'] ?? 0;
        $sheet->setCellValue("A{$statsRow}", "Paiements en attente:");
        $sheet->setCellValue("B{$statsRow}", "{$enAttente} ({$this->formatMontant($montantEnAttente)})");
        $statsRow++;

        // Taux de recouvrement
        if (isset($this->stats['recovery_rate'])) {
            $sheet->setCellValue("A{$statsRow}", "Taux de recouvrement:");
            $sheet->setCellValue("B{$statsRow}", $this->stats['recovery_rate'] . '%');
            $statsRow++;
        }
    }

    /**
     * Ajouter les informations sur les filtres appliqués
     */
    private function addFiltersInfo($sheet, $highestRow)
    {
        $filtersRow = $highestRow + (empty($this->stats) ? 3 : 10);

        // Titre
        $sheet->setCellValue("A{$filtersRow}", "FILTRES APPLIQUÉS");
        $sheet->getStyle("A{$filtersRow}")->getFont()->setBold(true)->setSize(12);
        $sheet->mergeCells("A{$filtersRow}:D{$filtersRow}");
        $filtersRow++;

        if (isset($this->filters['search']) && !empty($this->filters['search'])) {
            $sheet->setCellValue("A{$filtersRow}", "Recherche:");
            $sheet->setCellValue("B{$filtersRow}", $this->filters['search']);
            $filtersRow++;
        }

        if (isset($this->filters['status']) && !empty($this->filters['status'])) {
            $sheet->setCellValue("A{$filtersRow}", "Statut:");
            $sheet->setCellValue("B{$filtersRow}", $this->getStatutLabel($this->filters['status']));
            $filtersRow++;
        }

        if (isset($this->filters['date_debut']) && !empty($this->filters['date_debut'])) {
            $sheet->setCellValue("A{$filtersRow}", "Date début:");
            $sheet->setCellValue("B{$filtersRow}", \Carbon\Carbon::parse($this->filters['date_debut'])->format('d/m/Y'));
            $filtersRow++;
        }

        if (isset($this->filters['date_fin']) && !empty($this->filters['date_fin'])) {
            $sheet->setCellValue("A{$filtersRow}", "Date fin:");
            $sheet->setCellValue("B{$filtersRow}", \Carbon\Carbon::parse($this->filters['date_fin'])->format('d/m/Y'));
            $filtersRow++;
        }

        // Date d'export
        $filtersRow++;
        $sheet->setCellValue("A{$filtersRow}", "Exporté le:");
        $sheet->setCellValue("B{$filtersRow}", now()->format('d/m/Y H:i'));
        $sheet->getStyle("A{$filtersRow}")->getFont()->setItalic(true);
        $sheet->getStyle("B{$filtersRow}")->getFont()->setItalic(true);
    }

    /**
     * Obtenir le label du statut
     */
    private function getStatutLabel($status)
    {
        switch ($status) {
            case 'en_attente':
                return 'En attente';
            case 'validé':
                return 'Validé';
            case 'rejeté':
                return 'Rejeté';
            default:
                return ucfirst($status);
        }
    }

    /**
     * Formater un montant
     */
    private function formatMontant($montant)
    {
        return number_format($montant, 0, ',', ' ') . ' FCFA';
    }
}
