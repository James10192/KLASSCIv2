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
 * Lot 15 — Export détaillé des paiements (xlsx).
 *
 * Utilisé par App\Services\PaiementExportService::exportExcel().
 * Aligné sur le rendu PDF (mêmes colonnes, même résumé filtres,
 * gestion ownership : la colonne "Encaissé par" n'est affichée
 * que si l'utilisateur a paiements.view).
 */
class PaiementDetailleExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    protected Collection $paiements;
    protected bool $showCreator;
    protected int $count;
    protected float $totalMontant;
    protected array $filtersSummary;
    protected array $context;
    protected array $settings;
    protected int $rowCounter = 0;

    public function __construct(
        Collection $paiements,
        bool $showCreator,
        int $count,
        float $totalMontant,
        array $filtersSummary = [],
        array $context = []
    ) {
        $this->paiements = $paiements;
        $this->showCreator = $showCreator;
        $this->count = $count;
        $this->totalMontant = $totalMontant;
        $this->filtersSummary = $filtersSummary;
        $this->context = $context;
        $this->settings = $this->loadDefaultSettings();
    }

    public function collection()
    {
        return $this->paiements;
    }

    public function headings(): array
    {
        $headings = [
            '#',
            'Date',
            'N° Reçu',
            'Matricule',
            'Nom étudiant',
            'Classe',
            'Mode',
            'Montant (FCFA)',
            'Statut',
        ];

        if ($this->showCreator) {
            $headings[] = 'Encaissé par';
        }

        return $headings;
    }

    public function map($paiement): array
    {
        $this->rowCounter++;

        $etu = $paiement->etudiant;
        $cls = optional($paiement->inscription)->classe;
        $row = [
            $this->rowCounter,
            $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '—',
            $paiement->numero_recu ?? '—',
            $etu->matricule ?? '—',
            $etu ? trim(($etu->nom ?? '') . ' ' . ($etu->prenoms ?? '')) : '—',
            $cls->name ?? '—',
            $paiement->mode_paiement ?? '—',
            (float) ($paiement->montant ?? 0),
            $this->statusLabel($paiement->status ?? $paiement->statut ?? ''),
        ];

        if ($this->showCreator) {
            $row[] = optional($paiement->createdBy)->name ?? '—';
        }

        return $row;
    }

    public function title(): string
    {
        return 'Paiements détaillés';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Header bandeau (5 lignes)
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

                    // Format monétaire colonne H (Montant)
                    $sheet->getStyle("H{$dataStartRow}:H{$dataEndRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0 "FCFA"');
                }

                $sheet->freezePane('A7');
                $sheet->setAutoFilter("A{$headingRow}:{$highestColumn}{$headingRow}");

                // Total + filtres récap
                $currentRow = $dataEndRow + 2;
                $currentRow = $this->renderTotal($sheet, $currentRow, $highestColumn);
                $this->renderFilters($sheet, $currentRow + 1, $highestColumn);
            },
        ];
    }

    private function renderHeader(Worksheet $sheet, string $highestColumn): void
    {
        $schoolName = $this->settings['school_name'] ?? config('app.name');
        $sheet->mergeCells("A1:{$highestColumn}1");
        $sheet->setCellValue('A1', Str::upper($schoolName));
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $contactParts = array_filter([
            $this->settings['school_address'] ?? null,
            $this->settings['school_phone'] ? 'Tél: ' . $this->settings['school_phone'] : null,
            $this->settings['school_email'] ? 'Email: ' . $this->settings['school_email'] : null,
        ]);

        if (!empty($contactParts)) {
            $sheet->mergeCells("A2:{$highestColumn}2");
            $sheet->setCellValue('A2', implode(' • ', $contactParts));
            $sheet->getStyle('A2')->applyFromArray([
                'font' => ['size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F6FEB']],
            ]);
            $sheet->getRowDimension(2)->setRowHeight(18);
        }

        $title = $this->context['title'] ?? 'Tableau détaillé des paiements';
        $sheet->mergeCells("A3:{$highestColumn}3");
        $sheet->setCellValue('A3', $title);
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8EDFD']],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        $exportDate = now()->format('d/m/Y H:i');
        $totalLabel = number_format($this->totalMontant, 0, ',', ' ') . ' FCFA';

        // Ligne 4 : 3 cellules récap (lignes / total / date)
        $third = max(1, intdiv($this->columnIndex($highestColumn), 3));
        $colA = 'A';
        $colB = $this->columnLetter($third + 1);
        $colC = $this->columnLetter($third * 2 + 1);

        $sheet->mergeCells("A4:" . $this->columnLetter($third) . "4");
        $sheet->setCellValue('A4', "Lignes : {$this->count}");

        $sheet->mergeCells($colB . "4:" . $this->columnLetter($third * 2) . "4");
        $sheet->setCellValue($colB . '4', "Total : {$totalLabel}");

        $sheet->mergeCells($colC . "4:{$highestColumn}4");
        $sheet->setCellValue($colC . '4', "Exporté le : {$exportDate}");

        $sheet->getStyle("A4:{$highestColumn}4")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1F2937']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F3FF']],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C7D2FE']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(20);

        // Sous-titre encaisseur si applicable (caissier paiements.view_own)
        if (!empty($this->context['subtitle_creator'])) {
            $sheet->mergeCells("A5:{$highestColumn}5");
            $sheet->setCellValue('A5', $this->context['subtitle_creator']);
            $sheet->getStyle('A5')->applyFromArray([
                'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }
        $sheet->getRowDimension(5)->setRowHeight(14);
    }

    private function styleHeadingRow(Worksheet $sheet, int $row, string $highestColumn): void
    {
        $range = "A{$row}:{$highestColumn}{$row}";
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0F1F4B']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);
    }

    private function styleDataRows(Worksheet $sheet, int $startRow, int $endRow, string $highestColumn): void
    {
        if ($startRow > $endRow) {
            return;
        }

        $range = "A{$startRow}:{$highestColumn}{$endRow}";
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('D1D5DB');

        // Zebra
        for ($row = $startRow; $row <= $endRow; $row++) {
            if (($row - $startRow) % 2 === 0) {
                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F8FAFC');
            }
        }

        // Alignements
        $sheet->getStyle("A{$startRow}:A{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$startRow}:B{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$startRow}:D{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("E{$startRow}:F{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("H{$startRow}:H{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("I{$startRow}:I{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function applyStatusBadges(Worksheet $sheet, int $startRow, int $endRow): void
    {
        for ($row = $startRow; $row <= $endRow; $row++) {
            $value = trim((string) $sheet->getCell("I{$row}")->getValue());
            if ($value === '' || $value === '—') {
                continue;
            }

            $normalized = Str::lower($value);
            $style = $sheet->getStyle("I{$row}");

            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $style->getFont()->setBold(true);
            $style->getFill()->setFillType(Fill::FILL_SOLID);

            $fontColor = 'FFFFFF';
            switch ($normalized) {
                case 'validé':
                case 'valide':
                    $style->getFill()->getStartColor()->setRGB('16A34A');
                    break;
                case 'en attente':
                    $style->getFill()->getStartColor()->setRGB('F59E0B');
                    break;
                case 'rejeté':
                case 'rejete':
                    $style->getFill()->getStartColor()->setRGB('DC2626');
                    break;
                default:
                    $style->getFill()->getStartColor()->setRGB('E5E7EB');
                    $fontColor = '374151';
                    break;
            }
            $style->getFont()->getColor()->setRGB($fontColor);
        }
    }

    private function renderTotal(Worksheet $sheet, int $row, string $highestColumn): int
    {
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL ENCAISSÉ');
        $sheet->setCellValue("H{$row}", $this->totalMontant);

        $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0453CB']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '0453CB']],
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C7D2FE']],
            ],
        ]);
        $sheet->getStyle("A{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0 "FCFA"');
        $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(22);

        return $row;
    }

    private function renderFilters(Worksheet $sheet, int $row, string $highestColumn): int
    {
        if (empty($this->filtersSummary)) {
            return $row;
        }

        $sheet->mergeCells("A{$row}:{$highestColumn}{$row}");
        $sheet->setCellValue("A{$row}", 'FILTRES APPLIQUÉS');
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(20);

        $row++;
        foreach ($this->filtersSummary as $f) {
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->mergeCells("D{$row}:{$highestColumn}{$row}");
            $sheet->setCellValue("A{$row}", $f['label']);
            $sheet->setCellValue("D{$row}", $f['value']);

            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '1E3A8A']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E7FF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("D{$row}:{$highestColumn}{$row}")->applyFromArray([
                'font' => ['color' => ['rgb' => '111827']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('D1D5DB');
            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;
        }

        $row++;
        $sheet->mergeCells("A{$row}:{$highestColumn}{$row}");
        $sheet->setCellValue("A{$row}", 'Document généré le ' . now()->format('d/m/Y à H:i'));
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        return $row;
    }

    private function statusLabel(string $status): string
    {
        $normalized = Str::lower(trim($status));
        return match ($normalized) {
            'validé', 'valide' => 'Validé',
            'en_attente', 'en attente' => 'En attente',
            'rejeté', 'rejete' => 'Rejeté',
            'annulé', 'annule' => 'Annulé',
            '' => '—',
            default => ucfirst($status),
        };
    }

    private function loadDefaultSettings(): array
    {
        return [
            'school_name' => SettingsHelper::get('school_name', config('app.name')),
            'school_address' => SettingsHelper::get('school_address'),
            'school_phone' => SettingsHelper::get('school_phone'),
            'school_email' => SettingsHelper::get('school_email'),
        ];
    }

    private function columnIndex(string $letter): int
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($letter);
    }

    private function columnLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }
}
