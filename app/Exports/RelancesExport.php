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

class RelancesExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    protected $relances;
    protected $kpis;
    protected $filters;
    protected $settings;
    protected $rowCounter = 0;

    public function __construct(Collection $relances, array $kpis = [], array $filters = [], array $settings = [])
    {
        $this->relances = $relances;
        $this->kpis = $kpis;
        $this->filters = $filters;
        $this->settings = !empty($settings) ? $settings : $this->loadDefaultSettings();
    }

    public function collection()
    {
        return $this->relances;
    }

    public function headings(): array
    {
        return [
            'N°',
            'Matricule',
            'Nom',
            'Prénoms',
            'Classe',
            'Filière',
            'Total dû (FCFA)',
            'Total payé (FCFA)',
            'Solde restant (FCFA)',
            '% payé',
            'Niveau risque',
        ];
    }

    public function map($row): array
    {
        $this->rowCounter++;

        $totalDu = $row['total_du'] ?? 0;
        $totalPaye = $row['total_paye'] ?? 0;
        $soldeRestant = $row['solde_restant'] ?? 0;
        $pctPaye = $totalDu > 0 ? round(($totalPaye / $totalDu) * 100, 1) : 0;

        return [
            $this->rowCounter,
            $row['matricule'] ?? 'N/A',
            $row['nom'] ?? '',
            $row['prenoms'] ?? '',
            $row['classe'] ?? 'N/A',
            $row['filiere'] ?? 'N/A',
            $totalDu,
            $totalPaye,
            $soldeRestant,
            $pctPaye . '%',
            $this->getRiskLabel($row['risk_level'] ?? 'low'),
        ];
    }

    public function title(): string
    {
        return 'Gestion des Relances';
    }

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
                    $this->applyRiskBadges($sheet, $dataStartRow, $dataEndRow);

                    // Format montants
                    foreach (['G', 'H', 'I'] as $col) {
                        $sheet->getStyle("{$col}{$dataStartRow}:{$col}{$dataEndRow}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0 "FCFA"');
                    }
                }

                $sheet->freezePane('A7');
                $sheet->setAutoFilter("A{$headingRow}:{$highestColumn}{$headingRow}");

                $currentRow = $dataEndRow;
                $currentRow = $this->addKpis($sheet, $currentRow, $highestColumn);
                $this->addFiltersInfo($sheet, $currentRow, $highestColumn);
            },
        ];
    }

    private function renderHeader(Worksheet $sheet, string $highestColumn): void
    {
        $schoolName = $this->settings['school_name'] ?? config('app.name');

        $sheet->mergeCells("A1:{$highestColumn}1");
        $sheet->setCellValue('A1', Str::upper($schoolName));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
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
                'font'      => ['size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F6FEB']],
            ]);
            $sheet->getRowDimension(2)->setRowHeight(20);
        }

        $sheet->mergeCells("A3:{$highestColumn}3");
        $sheet->setCellValue('A3', 'RAPPORT DE GESTION DES RELANCES — ÉTUDIANTS AVEC SOLDES IMPAYÉS');
        $sheet->getStyle('A3')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8EDFD']],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        // KPI row
        $totalImpaye = $this->kpis['total_impaye'] ?? 0;
        $nbCritical  = $this->kpis['nb_critical'] ?? 0;
        $nbHigh      = $this->kpis['nb_high'] ?? 0;
        $nbRelances  = $this->kpis['nb_relances'] ?? $this->relances->count();
        $exportDate  = now()->format('d/m/Y H:i');

        $sheet->mergeCells('A4:C4');
        $sheet->setCellValue('A4', 'Total impayé : ' . $this->formatMontant($totalImpaye));
        $sheet->mergeCells('D4:F4');
        $sheet->setCellValue('D4', 'Critiques : ' . $nbCritical . ' | Élevés : ' . $nbHigh);
        $sheet->mergeCells('G4:I4');
        $sheet->setCellValue('G4', 'Nb étudiants : ' . $nbRelances);
        $sheet->mergeCells("J4:{$highestColumn}4");
        $sheet->setCellValue('J4', "Exporté le : {$exportDate}");

        $sheet->getStyle("A4:{$highestColumn}4")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1F2937']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F3FF']],
            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C7D2FE']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(20);

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
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');

        for ($row = $startRow; $row <= $endRow; $row++) {
            if (($row - $startRow) % 2 === 0) {
                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }
        }

        $sheet->getStyle("A{$startRow}:B{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$startRow}:F{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("G{$startRow}:I{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("J{$startRow}:J{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function applyRiskBadges(Worksheet $sheet, int $startRow, int $endRow): void
    {
        // Column K = index 11 = risk level
        for ($row = $startRow; $row <= $endRow; $row++) {
            $value = trim((string) $sheet->getCell("K{$row}")->getValue());
            if ($value === '') {
                continue;
            }

            $style = $sheet->getStyle("K{$row}");
            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $style->getFont()->setBold(true);

            $fill      = $style->getFill();
            $fill->setFillType(Fill::FILL_SOLID);
            $fontColor = 'FFFFFF';

            switch ($value) {
                case 'Critique':
                    $fill->getStartColor()->setRGB('1e293b'); // KLASSCI dark
                    break;
                case 'Élevé':
                    $fill->getStartColor()->setRGB('0453CB'); // KLASSCI primary
                    break;
                case 'Moyen':
                    $fill->getStartColor()->setRGB('5e91de'); // KLASSCI secondary
                    break;
                case 'Faible':
                    $fill->getStartColor()->setRGB('10b981'); // KLASSCI success
                    break;
                default:
                    $fill->getStartColor()->setRGB('E5E7EB');
                    $fontColor = '374151';
                    break;
            }

            $style->getFont()->getColor()->setRGB($fontColor);
        }
    }

    private function addKpis(Worksheet $sheet, int $startRow, string $highestColumn): int
    {
        $titleRow = $startRow + 2;

        $sheet->mergeCells("A{$titleRow}:{$highestColumn}{$titleRow}");
        $sheet->setCellValue("A{$titleRow}", 'RÉCAPITULATIF KPIs');
        $sheet->getStyle("A{$titleRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
        ]);
        $sheet->getRowDimension($titleRow)->setRowHeight(20);

        $metrics = [
            ['label' => 'Nombre total d\'étudiants à relancer',         'value' => $this->kpis['nb_relances'] ?? $this->relances->count()],
            ['label' => 'Total impayé (tous étudiants)',                'value' => $this->formatMontant($this->kpis['total_impaye'] ?? 0)],
            ['label' => 'Étudiants critiques (aucun paiement)',         'value' => $this->kpis['nb_critical'] ?? 0],
            ['label' => 'Étudiants à risque élevé (partiel < 25%)',    'value' => $this->kpis['nb_high'] ?? 0],
            ['label' => 'Étudiants à risque moyen (partiel > 25%)',    'value' => $this->kpis['nb_medium'] ?? 0],
            ['label' => 'Étudiants à risque faible (> 75% payé)',      'value' => $this->kpis['nb_low'] ?? 0],
        ];

        $currentRow = $titleRow + 1;

        foreach ($metrics as $metric) {
            $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
            $sheet->mergeCells("H{$currentRow}:{$highestColumn}{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", $metric['label']);
            $sheet->setCellValue("H{$currentRow}", $metric['value']);

            $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => '1E3A8A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E7FF']],
            ]);
            $sheet->getStyle("H{$currentRow}:{$highestColumn}{$currentRow}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => '111827']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
            ]);
            $sheet->getStyle("A{$currentRow}:{$highestColumn}{$currentRow}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('C7D2FE');
            $sheet->getRowDimension($currentRow)->setRowHeight(18);

            $currentRow++;
        }

        return $currentRow;
    }

    private function addFiltersInfo(Worksheet $sheet, int $startRow, string $highestColumn): int
    {
        $titleRow = $startRow + 2;

        $sheet->mergeCells("A{$titleRow}:{$highestColumn}{$titleRow}");
        $sheet->setCellValue("A{$titleRow}", 'FILTRES APPLIQUÉS');
        $sheet->getStyle("A{$titleRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
        ]);
        $sheet->getRowDimension($titleRow)->setRowHeight(20);

        $filters = [];

        if (!empty($this->filters['search'])) {
            $filters[] = ['label' => 'Recherche', 'value' => $this->filters['search']];
        }
        if (!empty($this->filters['filiere'])) {
            $filters[] = ['label' => 'Filière', 'value' => $this->filters['filiere']];
        }
        if (!empty($this->filters['classe'])) {
            $filters[] = ['label' => 'Classe', 'value' => $this->filters['classe']];
        }
        if (!empty($this->filters['annee'])) {
            $filters[] = ['label' => 'Année universitaire', 'value' => $this->filters['annee']];
        }
        if (!empty($this->filters['risk'])) {
            $filters[] = ['label' => 'Niveau de risque', 'value' => $this->getRiskLabel($this->filters['risk'])];
        }

        if (empty($filters)) {
            $filters[] = ['label' => 'Aucun filtre spécifique appliqué', 'value' => ''];
        }

        $currentRow = $titleRow + 1;

        foreach ($filters as $filter) {
            $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
            $sheet->mergeCells("H{$currentRow}:{$highestColumn}{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", $filter['label']);
            $sheet->setCellValue("H{$currentRow}", $filter['value']);

            $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => '1E3A8A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E7FF']],
            ]);
            $sheet->getStyle("H{$currentRow}:{$highestColumn}{$currentRow}")->applyFromArray([
                'font'      => ['color' => ['rgb' => '111827']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
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

        return $currentRow;
    }

    private function getRiskLabel(string $risk): string
    {
        return match ($risk) {
            'critical' => 'Critique',
            'high'     => 'Élevé',
            'medium'   => 'Moyen',
            'low'      => 'Faible',
            default    => ucfirst($risk),
        };
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
