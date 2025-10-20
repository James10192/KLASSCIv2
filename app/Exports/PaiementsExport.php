<?php

namespace App\Exports;

use App\Helpers\SettingsHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaiementsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    protected $paiements;
    protected $stats;
    protected $filters;
    protected $settings;
    protected $rowCounter = 0;

    public function __construct(Collection $paiements, array $stats = [], array $filters = [], array $settings = [])
    {
        $this->paiements = $paiements;
        $this->stats = $stats;
        $this->filters = $filters;
        $this->settings = !empty($settings) ? $settings : $this->loadDefaultSettings();
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
        $this->rowCounter++;

        $etudiant = $paiement->etudiant;
        $inscription = $paiement->inscription;
        $fraisCategory = $paiement->fraisCategory;
        $categorie = $paiement->categorie; // Ancien système (fallback)

        return [
            $this->rowCounter,
            $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : 'N/A',
            $etudiant ? $etudiant->matricule : 'N/A',
            $etudiant ? $etudiant->nom : '',
            $etudiant ? $etudiant->prenoms : '',
            $inscription && $inscription->classe ? $inscription->classe->name : 'N/A',
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
     * Événements pour ajouter des totaux et informations
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 5);

                $highestColumn = $sheet->getHighestDataColumn();
                $headingRow = 6;
                $dataStartRow = $headingRow + 1;

                $this->renderHeader($sheet, $highestColumn);
                $this->styleHeadingRow($sheet, $headingRow, $highestColumn);

                $dataEndRow = max($sheet->getHighestRow(), $headingRow);

                if ($dataEndRow >= $dataStartRow) {
                    $this->styleDataRows($sheet, $dataStartRow, $dataEndRow, $highestColumn);
                    $this->applyStatusBadges($sheet, $dataStartRow, $dataEndRow);

                    $sheet->getStyle("J{$dataStartRow}:J{$dataEndRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0 "FCFA"');
                }

                $sheet->freezePane('A7');
                $sheet->setAutoFilter("A{$headingRow}:{$highestColumn}{$headingRow}");

                $currentRow = $dataEndRow;
                $currentRow = $this->addStatistics($sheet, $currentRow, $highestColumn);
                $this->addFiltersInfo($sheet, $currentRow, $highestColumn);
            },
        ];
    }

    /**
     * Ajoute le bandeau d'en-tête avec les informations de l'établissement
     */
    private function renderHeader(Worksheet $sheet, string $highestColumn): void
    {
        $schoolName = $this->settings['school_name'] ?? config('app.name');
        $sheet->mergeCells("A1:{$highestColumn}1");
        $sheet->setCellValue('A1', Str::upper($schoolName));
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0453CB'],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $contactParts = array_filter([
            $this->settings['school_address'] ?? null,
            $this->settings['school_phone'] ? 'Tel: ' . $this->settings['school_phone'] : null,
            $this->settings['school_email'] ? 'Email: ' . $this->settings['school_email'] : null,
        ]);

        if (!empty($contactParts)) {
            $sheet->mergeCells("A2:{$highestColumn}2");
            $sheet->setCellValue('A2', implode(' • ', $contactParts));
            $sheet->getStyle('A2')->applyFromArray([
                'font' => [
                    'size' => 10,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F6FEB'],
                ],
            ]);
            $sheet->getRowDimension(2)->setRowHeight(20);
        }

        $sheet->mergeCells("A3:{$highestColumn}3");
        $sheet->setCellValue('A3', 'Tableau de suivi des paiements');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => '0F172A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8EDFD'],
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        $totalPaiements = $this->stats['total'] ?? $this->paiements->count();
        $montantTotal = $this->stats['montant_total'] ?? $this->paiements->sum('montant');
        $exportDate = now()->format('d/m/Y H:i');

        $sheet->mergeCells('A4:F4');
        $sheet->setCellValue('A4', "Total paiements : {$totalPaiements}");
        $sheet->mergeCells('G4:L4');
        $sheet->setCellValue('G4', 'Montant total : ' . $this->formatMontant($montantTotal));
        $sheet->mergeCells("M4:{$highestColumn}4");
        $sheet->setCellValue('M4', "Exporté le : {$exportDate}");
        $sheet->getStyle("A4:{$highestColumn}4")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '1F2937'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F3FF'],
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'C7D2FE'],
                ],
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(20);

        $sheet->mergeCells("A5:{$highestColumn}5");
        $sheet->getRowDimension(5)->setRowHeight(6);
    }

    /**
     * Style la ligne d'en-tête du tableau
     */
    private function styleHeadingRow(Worksheet $sheet, int $row, string $highestColumn): void
    {
        $range = "A{$row}:{$highestColumn}{$row}";
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0453CB'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '0F1F4B'],
                ],
            ],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);
    }

    /**
     * Applique le style général aux lignes de données
     */
    private function styleDataRows(Worksheet $sheet, int $startRow, int $endRow, string $highestColumn): void
    {
        if ($startRow > $endRow) {
            return;
        }

        $dataRange = "A{$startRow}:{$highestColumn}{$endRow}";

        $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('D1D5DB');

        for ($row = $startRow; $row <= $endRow; $row++) {
            if (($row - $startRow) % 2 === 0) {
                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F8FAFC');
            }
        }

        $sheet->getStyle("A{$startRow}:A{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$startRow}:B{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$startRow}:E{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("F{$startRow}:I{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("J{$startRow}:J{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("M{$startRow}:N{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("O{$startRow}:O{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("P{$startRow}:P{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
        $sheet->getStyle("I{$startRow}:I{$endRow}")->getAlignment()->setWrapText(true);
    }

    /**
     * Ajoute un style "badge" sur la colonne des statuts
     */
    private function applyStatusBadges(Worksheet $sheet, int $startRow, int $endRow): void
    {
        for ($row = $startRow; $row <= $endRow; $row++) {
            $value = trim((string) $sheet->getCell("L{$row}")->getValue());

            if ($value === '') {
                continue;
            }

            $normalized = Str::lower($value);
            $style = $sheet->getStyle("L{$row}");

            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $style->getFont()->setBold(true);

            $fill = $style->getFill();
            $fill->setFillType(Fill::FILL_SOLID);

            $fontColor = 'FFFFFF';
            switch ($normalized) {
                case 'validé':
                case 'valide':
                    $fill->getStartColor()->setRGB('22C55E');
                    break;
                case 'en attente':
                    $fill->getStartColor()->setRGB('F59E0B');
                    break;
                case 'rejeté':
                case 'rejete':
                    $fill->getStartColor()->setRGB('EF4444');
                    break;
                default:
                    $fill->getStartColor()->setRGB('E5E7EB');
                    $fontColor = '374151';
                    break;
            }

            $style->getFont()->getColor()->setRGB($fontColor);
        }
    }

    /**
     * Ajouter les statistiques en bas de la feuille
     */
    private function addStatistics(Worksheet $sheet, int $startRow, string $highestColumn): int
    {
        if (empty($this->stats)) {
            return $startRow;
        }

        $titleRow = $startRow + 2;
        $sheet->mergeCells("A{$titleRow}:{$highestColumn}{$titleRow}");
        $sheet->setCellValue("A{$titleRow}", 'STATISTIQUES');
        $sheet->getStyle("A{$titleRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0453CB'],
            ],
        ]);
        $sheet->getRowDimension($titleRow)->setRowHeight(20);

        $metrics = [
            [
                'label' => 'Nombre total de paiements',
                'value' => $this->stats['total'] ?? $this->paiements->count(),
            ],
            [
                'label' => 'Montant total encaissé',
                'value' => $this->formatMontant($this->stats['montant_total'] ?? $this->paiements->sum('montant')),
            ],
            [
                'label' => 'Paiements validés',
                'value' => sprintf(
                    '%s (%s)',
                    $this->stats['valides'] ?? 0,
                    $this->formatMontant($this->stats['montant_valide'] ?? 0)
                ),
            ],
            [
                'label' => 'Paiements en attente',
                'value' => sprintf(
                    '%s (%s)',
                    $this->stats['en_attente'] ?? 0,
                    $this->formatMontant($this->stats['montant_en_attente'] ?? 0)
                ),
            ],
        ];

        if (isset($this->stats['recovery_rate'])) {
            $metrics[] = [
                'label' => 'Taux de recouvrement',
                'value' => $this->stats['recovery_rate'] . ' %',
            ];
        }

        $currentRow = $titleRow + 1;

        foreach ($metrics as $metric) {
            $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
            $sheet->mergeCells("H{$currentRow}:{$highestColumn}{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", $metric['label']);
            $sheet->setCellValue("H{$currentRow}", $metric['value']);

            $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '1E3A8A'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E7FF'],
                ],
            ]);

            $sheet->getStyle("H{$currentRow}:{$highestColumn}{$currentRow}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '111827'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F9FAFB'],
                ],
            ]);

            $sheet->getStyle("A{$currentRow}:{$highestColumn}{$currentRow}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('C7D2FE');

            $sheet->getRowDimension($currentRow)->setRowHeight(18);
            $currentRow++;
        }

        return $currentRow;
    }

    /**
     * Ajouter les informations sur les filtres appliqués
     */
    private function addFiltersInfo(Worksheet $sheet, int $startRow, string $highestColumn): int
    {
        $titleRow = $startRow + 2;

        $sheet->mergeCells("A{$titleRow}:{$highestColumn}{$titleRow}");
        $sheet->setCellValue("A{$titleRow}", 'FILTRES APPLIQUÉS');
        $sheet->getStyle("A{$titleRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0453CB'],
            ],
        ]);
        $sheet->getRowDimension($titleRow)->setRowHeight(20);

        $filters = [];

        if (!empty($this->filters['search'])) {
            $filters[] = [
                'label' => 'Recherche',
                'value' => $this->filters['search'],
            ];
        }

        if (!empty($this->filters['status'])) {
            $filters[] = [
                'label' => 'Statut',
                'value' => $this->getStatutLabel($this->filters['status']),
            ];
        }

        if (!empty($this->filters['date_debut'])) {
            $filters[] = [
                'label' => 'Date début',
                'value' => \Carbon\Carbon::parse($this->filters['date_debut'])->format('d/m/Y'),
            ];
        }

        if (!empty($this->filters['date_fin'])) {
            $filters[] = [
                'label' => 'Date fin',
                'value' => \Carbon\Carbon::parse($this->filters['date_fin'])->format('d/m/Y'),
            ];
        }

        if (empty($filters)) {
            $filters[] = [
                'label' => 'Aucun filtre spécifique appliqué',
                'value' => '',
            ];
        }

        $currentRow = $titleRow + 1;

        foreach ($filters as $filter) {
            $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
            $sheet->mergeCells("H{$currentRow}:{$highestColumn}{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", $filter['label']);
            $sheet->setCellValue("H{$currentRow}", $filter['value']);

            $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '1E3A8A'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E7FF'],
                ],
            ]);

            $sheet->getStyle("H{$currentRow}:{$highestColumn}{$currentRow}")->applyFromArray([
                'font' => [
                    'color' => ['rgb' => '111827'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFFFFF'],
                ],
            ]);

            $sheet->getStyle("A{$currentRow}:{$highestColumn}{$currentRow}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('D1D5DB');

            $sheet->getRowDimension($currentRow)->setRowHeight(18);
            $currentRow++;
        }

        $sheet->mergeCells("A{$currentRow}:{$highestColumn}{$currentRow}");
        $sheet->setCellValue("A{$currentRow}", 'Export généré le ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle("A{$currentRow}")->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '64748B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        return $currentRow;
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
            case 'valide':
                return 'Validé';
            case 'rejeté':
            case 'rejete':
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
        return number_format((float) $montant, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Chargement des paramètres école par défaut
     */
    private function loadDefaultSettings(): array
    {
        return [
            'school_name' => SettingsHelper::get('school_name', config('app.name')),
            'school_address' => SettingsHelper::get('school_address'),
            'school_phone' => SettingsHelper::get('school_phone'),
            'school_email' => SettingsHelper::get('school_email'),
        ];
    }
}
