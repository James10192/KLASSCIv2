<?php

namespace App\Exports;

use App\Domain\Notifications\PhoneFormatter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Excel Recouvrement quotidien — colonnes alignées avec le PDF.
 * Téléphone en format texte (sinon Excel perd le `+` initial du E.164).
 */
class RecouvrementExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithStyles,
    WithColumnFormatting,
    WithEvents,
    ShouldAutoSize
{
    private int $counter = 0;

    /**
     * @param  array<int, array>  $rows  Top-N at-risk students with full enrichment
     * @param  array<string, string>  $filters  Applied filters for recap line
     */
    public function __construct(
        private readonly array $rows,
        private readonly array $filters = [],
    ) {}

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function title(): string
    {
        return 'Recouvrement';
    }

    public function headings(): array
    {
        return [
            '#',
            'Étudiant',
            'Classe',
            'Téléphone',
            'Email',
            'Solde restant (FCFA)',
            'Jours retard',
            '% payé',
            'Niveau',
            'Score',
        ];
    }

    public function map($row): array
    {
        $this->counter++;
        return [
            $this->counter,
            $row['etudiant_nom'] ?? '',
            $row['classe_nom'] ?? '',
            PhoneFormatter::toReadable($row['phone'] ?? null) ?? '—',
            $row['email'] ?? '—',
            (float) ($row['solde_restant'] ?? 0),
            (int) ($row['jours_retard'] ?? 0),
            round(((float) ($row['ratio_paye'] ?? 0)) * 100, 1) . ' %',
            ucfirst($row['level'] ?? ''),
            round((float) ($row['score'] ?? 0), 3),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_TEXT,
            'F' => '#,##0',
            'G' => '#,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A1:J{$highestRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                if ($highestRow > 1) {
                    $sheet->getStyle("A2:J{$highestRow}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                        ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E2E8F0'));
                }
            },
        ];
    }
}
