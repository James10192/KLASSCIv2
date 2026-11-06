<?php

namespace App\Exports;

use App\Helpers\SettingsHelper;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

final class SituationClassesExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithColumnFormatting, WithEvents, WithDrawings, ShouldAutoSize
{
    private readonly array $school;
    private readonly array $theme;

    public function __construct(private readonly array $rapport, private readonly array $filters)
    {
        $this->school = SettingsHelper::getSchoolInfo();
        $this->theme = SettingsHelper::getPdfSettings();
    }

    public function title(): string
    {
        return 'Situation classes';
    }

    public function collection(): Collection
    {
        return $this->rapport['classes']->concat([['__total' => true, ...$this->rapport['totaux']]]);
    }

    public function headings(): array
    {
        $filtre = collect($this->filters)->map(fn ($v, $k) => "$k : $v")->join('  |  ');
        return [
            [$this->school['name'] ?? 'Établissement'],
            ['ÉTAT FINANCIER PAR CLASSE'],
            [$filtre ?: 'Toutes les classes — année courante'],
            ['Classe', 'Filière / mention', 'Niveau', 'Inscrits', 'Attendu', 'Règlements imputés', 'Encaissements nets', 'Écart imputation / caisse', 'Reste à payer', 'Échu impayé', 'Recouvré (%)'],
        ];
    }

    public function map($row): array
    {
        $total = isset($row['__total']);
        $categorie = (bool) $this->rapport['filtered_category'];
        return [
            $total ? 'TOTAL GÉNÉRAL' : $row['classe'],
            $total ? '' : $row['filiere'],
            $total ? '' : $row['niveau'],
            (int) $row['effectif'],
            (float) $row['attendu'],
            (float) $row['regle'],
            $categorie ? null : (float) $row['encaisse'],
            $categorie ? null : (float) $row['ajustements'],
            (float) $row['restant'],
            (float) $row['echu'],
            $row['taux'] === null ? null : (float) $row['taux'],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '#,##0',
            'E' => '#,##0.00',
            'F' => '#,##0.00',
            'G' => '#,##0.00',
            'H' => '#,##0.00',
            'I' => '#,##0.00',
            'J' => '#,##0.00',
            'K' => '0.0"%"',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $last = $sheet->getHighestRow();
                $primary = ltrim((string) ($this->theme['primary_color'] ?? '#0453cb'), '#');
                if (! preg_match('/^[a-f0-9]{6}$/i', $primary)) {
                    $primary = '0453CB';
                }
                $sheet->mergeCells('A1:J1');
                $sheet->mergeCells('A2:J2');
                $sheet->mergeCells('A3:J3');
                $sheet->getRowDimension(1)->setRowHeight(40);
                $sheet->getRowDimension(2)->setRowHeight(27);
                $sheet->getRowDimension(3)->setRowHeight(25);
                $sheet->getRowDimension(4)->setRowHeight(35);
                foreach ([1, 2, 4] as $row) {
                    $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $primary]],
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => $row === 2 ? 15 : 11],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }
                $sheet->getStyle('A3:K3')->getFont()->getColor()->setRGB('475569');
                $sheet->getStyle('A4:K4')->getAlignment()->setWrapText(true);
                $sheet->getStyle("D5:K{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                if ($last >= 5) {
                    $sheet->getStyle("A5:K{$last}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);
                    $sheet->getStyle("A{$last}:K{$last}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $primary]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'EAF1FB']],
                    ]);
                }
                $sheet->freezePane('E5');
                $sheet->setAutoFilter("A4:K{$last}");
                foreach (['A' => 29, 'B' => 30, 'C' => 22, 'D' => 12, 'E' => 20,
                          'F' => 22, 'G' => 22, 'H' => 25, 'I' => 20, 'J' => 20, 'K' => 17] as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                    $sheet->getColumnDimension($col)->setAutoSize(false);
                }
            },
        ];
    }

    public function drawings(): array
    {
        if (! ($this->theme['show_logo'] ?? true)) {
            return [];
        }
        $logo = ltrim((string) ($this->school['logo'] ?? ''), '/');
        if ($logo === '') {
            return [];
        }
        $logo = preg_replace('#^storage/#', '', str_replace('\\', '/', $logo));
        foreach ([
            storage_path('app/public/'.$logo),
            public_path('storage/'.$logo),
            public_path($logo),
        ] as $path) {
            if (! is_file($path) || ! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['png', 'jpeg', 'jpg', 'gif'], true)) {
                continue;
            }
            $drawing = new Drawing();
            $drawing->setName('Logo établissement');
            $drawing->setPath($path);
            $drawing->setHeight(40);
            $drawing->setCoordinates('K1');

            return [$drawing];
        }

        return [];
    }
}
