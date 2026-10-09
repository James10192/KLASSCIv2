<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class EnseignantsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    private int $number = 0;

    public function __construct(private readonly array $rows) {}

    public function collection(): Collection { return collect($this->rows); }

    public function headings(): array
    {
        return ['N°', 'Matricule', 'Enseignant', 'Téléphone', 'Email', 'Spécialisation', 'Régime', 'Statut'];
    }

    public function map($row): array
    {
        return [
            ++$this->number,
            $this->safeText($row['matricule']),
            $this->safeText($row['nom']),
            $this->safeText($row['telephone']),
            $this->safeText($row['email']),
            $this->safeText($row['specialisation']),
            $row['regime'],
            $row['statut'],
        ];
    }

    // Evite l'execution de formules injectees via des champs libres.
    private function safeText(string $value): string
    {
        return preg_match('/^[=+@\-\t\r]/u', $value) && ! preg_match('/^\+[0-9 ]+$/', $value)
            ? "'" . $value : $value;
    }

    public function columnFormats(): array
    {
        return ['B' => NumberFormat::FORMAT_TEXT, 'D' => NumberFormat::FORMAT_TEXT, 'E' => NumberFormat::FORMAT_TEXT];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:H' . max(1, count($this->rows) + 1));
        return [1 => [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
        ]];
    }
}
