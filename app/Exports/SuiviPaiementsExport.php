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

/**
 * Export Excel de la liste des étudiants par statut de paiement
 * (suivi-categories : aucun paiement, partiels, à jour)
 */
class SuiviPaiementsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    /** @var Collection Collection d'items ['inscription'=>..., 'montant_attendu'=>..., 'montant_paye'=>..., 'solde'=>..., 'pourcentage'=>...] */
    protected $etudiants;
    protected $category;
    protected $statutLabel;
    protected $stats;
    protected $filters;
    protected $settings;
    protected $rowCounter = 0;

    public function __construct(
        Collection $etudiants,
        $category,
        string $statutLabel,
        array $stats = [],
        array $filters = [],
        array $settings = []
    ) {
        $this->etudiants   = $etudiants;
        $this->category    = $category;
        $this->statutLabel = $statutLabel;
        $this->stats       = $stats;
        $this->filters     = $filters;
        $this->settings    = !empty($settings) ? $settings : $this->loadDefaultSettings();
    }

    public function collection()
    {
        return $this->etudiants;
    }

    public function headings(): array
    {
        return [
            'N°',
            'Matricule',
            'Nom',
            'Prénom(s)',
            'Classe',
            'Filière',
            'Niveau',
            'Montant Dû (FCFA)',
            'Montant Payé (FCFA)',
            'Solde Restant (FCFA)',
            'Taux (%)',
        ];
    }

    public function map($item): array
    {
        $this->rowCounter++;

        $inscription = $item['inscription'];
        $etudiant    = $inscription->etudiant ?? null;

        return [
            $this->rowCounter,
            $etudiant ? ($etudiant->matricule ?? 'N/A') : 'N/A',
            $etudiant ? ($etudiant->nom ?? '') : '',
            $etudiant ? ($etudiant->prenoms ?? '') : '',
            $inscription->classe->name ?? ($inscription->niveauEtude->name ?? 'N/A'),
            $inscription->filiere->name ?? 'N/A',
            $inscription->niveauEtude->name ?? 'N/A',
            $item['montant_attendu'] ?? 0,
            $item['montant_paye'] ?? 0,
            $item['solde'] ?? 0,
            $item['pourcentage'] ?? 0,
        ];
    }

    public function title(): string
    {
        return Str::limit($this->statutLabel, 30);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 5);

                $highestColumn = $sheet->getHighestDataColumn();
                $headingRow    = 6;
                $dataStartRow  = $headingRow + 1;

                $this->renderHeader($sheet, $highestColumn);
                $this->styleHeadingRow($sheet, $headingRow, $highestColumn);

                $dataEndRow = max($sheet->getHighestRow(), $headingRow);

                if ($dataEndRow >= $dataStartRow) {
                    $this->styleDataRows($sheet, $dataStartRow, $dataEndRow, $highestColumn);
                    $this->applyAmountFormatting($sheet, $dataStartRow, $dataEndRow);
                }

                $sheet->freezePane('A7');
                $sheet->setAutoFilter("A{$headingRow}:{$highestColumn}{$headingRow}");

                $currentRow = $dataEndRow;
                $currentRow = $this->addStatistics($sheet, $currentRow, $highestColumn);
                $this->addFiltersInfo($sheet, $currentRow, $highestColumn);
            },
        ];
    }

    private function renderHeader(Worksheet $sheet, string $highestColumn): void
    {
        $schoolName = $this->settings['school_name'] ?? config('app.name');

        // Row 1 : nom établissement (bleu)
        $sheet->mergeCells("A1:{$highestColumn}1");
        $sheet->setCellValue('A1', Str::upper($schoolName));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Row 2 : coordonnées
        $addressParts = array_filter([
            $this->settings['school_address'] ?? null,
            $this->settings['school_city'] ?? null,
        ]);
        $contactParts = array_filter([
            !empty($addressParts) ? implode(', ', $addressParts) : null,
            isset($this->settings['school_phone']) && $this->settings['school_phone'] ? 'Tél : ' . $this->settings['school_phone'] : null,
            isset($this->settings['school_email']) && $this->settings['school_email'] ? 'Email : ' . $this->settings['school_email'] : null,
        ]);

        $sheet->mergeCells("A2:{$highestColumn}2");
        $sheet->setCellValue('A2', !empty($contactParts) ? implode(' • ', $contactParts) : '');
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F6FEB']],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Row 3 : titre du tableau
        $categoryName = $this->category->name ?? 'N/A';
        $title = "Suivi Paiements – {$categoryName} – {$this->statutLabel}";
        $sheet->mergeCells("A3:{$highestColumn}3");
        $sheet->setCellValue('A3', $title);
        $sheet->getStyle('A3')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8EDFD']],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        // Row 4 : totaux rapides
        $total        = $this->stats['total'] ?? $this->etudiants->count();
        $montantDu    = $this->stats['montant_total_du'] ?? $this->etudiants->sum(fn($e) => $e['montant_attendu'] ?? 0);
        $montantPaye  = $this->stats['montant_total_paye'] ?? $this->etudiants->sum(fn($e) => $e['montant_paye'] ?? 0);
        $exportDate   = now()->format('d/m/Y H:i');

        $sheet->mergeCells('A4:C4');
        $sheet->setCellValue('A4', "Étudiants : {$total}");
        $sheet->mergeCells('D4:F4');
        $sheet->setCellValue('D4', 'Dû : ' . $this->formatMontant($montantDu));
        $sheet->mergeCells('G4:I4');
        $sheet->setCellValue('G4', 'Payé : ' . $this->formatMontant($montantPaye));
        $sheet->mergeCells("J4:{$highestColumn}4");
        $sheet->setCellValue('J4', "Exporté le : {$exportDate}");
        $sheet->getStyle("A4:{$highestColumn}4")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1F2937']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F3FF']],
            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C7D2FE']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(20);

        // Row 5 : spacer
        $sheet->mergeCells("A5:{$highestColumn}5");
        $sheet->getRowDimension(5)->setRowHeight(6);
    }

    private function styleHeadingRow(Worksheet $sheet, int $row, string $highestColumn): void
    {
        $range = "A{$row}:{$highestColumn}{$row}";
        $sheet->getStyle($range)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0F1F4B']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);
    }

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
            $sheet->getRowDimension($row)->setRowHeight(18);
            if (($row - $startRow) % 2 === 0) {
                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
            // Aligner à droite les colonnes montants (H, I, J, K = colonnes 8-11)
            $sheet->getStyle("H{$row}:K{$row}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Aligner N° centré
        $sheet->getStyle("A{$startRow}:A{$endRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function applyAmountFormatting(Worksheet $sheet, int $startRow, int $endRow): void
    {
        // Colonnes H (Montant Dû), I (Montant Payé), J (Solde) → format FCFA
        foreach (['H', 'I', 'J'] as $col) {
            $sheet->getStyle("{$col}{$startRow}:{$col}{$endRow}")
                ->getNumberFormat()->setFormatCode('#,##0 "FCFA"');
        }
        // Colonne K (%) → format pourcentage
        $sheet->getStyle("K{$startRow}:K{$endRow}")
            ->getNumberFormat()->setFormatCode('0.0"%"');
    }

    private function addStatistics(Worksheet $sheet, int $afterRow, string $highestColumn): int
    {
        $currentRow = $afterRow + 2;

        $total       = $this->stats['total'] ?? $this->etudiants->count();
        $montantDu   = $this->stats['montant_total_du'] ?? $this->etudiants->sum(fn($e) => $e['montant_attendu'] ?? 0);
        $montantPaye = $this->stats['montant_total_paye'] ?? $this->etudiants->sum(fn($e) => $e['montant_paye'] ?? 0);
        $solde       = $montantDu - $montantPaye;
        $taux        = $montantDu > 0 ? round(($montantPaye / $montantDu) * 100, 1) : 0;

        // Titre section stats
        $sheet->mergeCells("A{$currentRow}:{$highestColumn}{$currentRow}");
        $sheet->setCellValue("A{$currentRow}", 'RÉCAPITULATIF');
        $sheet->getStyle("A{$currentRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
        ]);
        $sheet->getRowDimension($currentRow)->setRowHeight(20);
        $currentRow++;

        $statsData = [
            ['Total étudiants',    $total,                          ''],
            ['Montant total dû',   $this->formatMontant($montantDu),  ''],
            ['Montant total payé', $this->formatMontant($montantPaye), ''],
            ['Solde restant',      $this->formatMontant($solde),       ''],
            ['Taux de recouvrement', $taux . '%',                     ''],
        ];

        foreach ($statsData as $stat) {
            $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
            $sheet->mergeCells("G{$currentRow}:{$highestColumn}{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", $stat[0]);
            $sheet->setCellValue("G{$currentRow}", $stat[1]);
            $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => '1E3A8A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E7FF']],
            ]);
            $sheet->getStyle("G{$currentRow}:{$highestColumn}{$currentRow}")->applyFromArray([
                'font'      => ['color' => ['rgb' => '111827']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            ]);
            $sheet->getStyle("A{$currentRow}:{$highestColumn}{$currentRow}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
            $sheet->getRowDimension($currentRow)->setRowHeight(18);
            $currentRow++;
        }

        return $currentRow;
    }

    private function addFiltersInfo(Worksheet $sheet, int $currentRow, string $highestColumn): void
    {
        $currentRow += 1;

        // Titre
        $sheet->mergeCells("A{$currentRow}:{$highestColumn}{$currentRow}");
        $sheet->setCellValue("A{$currentRow}", 'FILTRES APPLIQUÉS');
        $sheet->getStyle("A{$currentRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
        ]);
        $sheet->getRowDimension($currentRow)->setRowHeight(20);
        $currentRow++;

        $filtersToShow = [
            ['Catégorie de frais', $this->category->name ?? 'N/A'],
            ['Statut affiché',     $this->statutLabel],
        ];

        if (!empty($this->filters['filiere'])) {
            $filtersToShow[] = ['Filière', $this->filters['filiere']];
        }
        if (!empty($this->filters['niveau'])) {
            $filtersToShow[] = ['Niveau', $this->filters['niveau']];
        }

        foreach ($filtersToShow as $filter) {
            $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
            $sheet->mergeCells("G{$currentRow}:{$highestColumn}{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", $filter[0]);
            $sheet->setCellValue("G{$currentRow}", $filter[1]);
            $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => '1E3A8A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E7FF']],
            ]);
            $sheet->getStyle("G{$currentRow}:{$highestColumn}{$currentRow}")->applyFromArray([
                'font'      => ['color' => ['rgb' => '111827']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            ]);
            $sheet->getStyle("A{$currentRow}:{$highestColumn}{$currentRow}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
            $sheet->getRowDimension($currentRow)->setRowHeight(18);
            $currentRow++;
        }

        $sheet->mergeCells("A{$currentRow}:{$highestColumn}{$currentRow}");
        $sheet->setCellValue("A{$currentRow}", 'Export généré le ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle("A{$currentRow}")->applyFromArray([
            'font'      => ['italic' => true, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    private function formatMontant($montant): string
    {
        return number_format((float) $montant, 0, ',', ' ') . ' FCFA';
    }

    private function loadDefaultSettings(): array
    {
        return [
            'school_name'    => SettingsHelper::get('school_name', config('app.name')),
            'school_address' => SettingsHelper::get('school_address'),
            'school_phone'   => SettingsHelper::get('school_phone'),
            'school_email'   => SettingsHelper::get('school_email'),
        ];
    }
}
