<?php

namespace App\Exports;

use App\Domain\Notifications\PhoneFormatter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Liste d'appel des familles a prevenir. Telephones en texte : Excel perdrait
 * sinon le « + » de l'indicatif.
 */
class FamillesAPrevenirExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    /** @param  list<array<string, mixed>>  $lignes */
    public function __construct(private readonly array $lignes)
    {
    }

    public function collection(): Collection
    {
        return collect($this->lignes);
    }

    public function title(): string
    {
        return 'Familles à prévenir';
    }

    public function headings(): array
    {
        return ['Date', 'Créneau', 'Nom', 'Prénoms', 'Téléphone', 'Second contact', 'Téléphone du second contact', 'Référence', 'Pourquoi', 'Prévenue ?'];
    }

    public function map($ligne): array
    {
        return [
            $ligne['jour'],
            $ligne['heure'],
            $ligne['nom'],
            $ligne['prenoms'],
            PhoneFormatter::toReadable($ligne['telephone']) ?? $ligne['telephone'],
            $ligne['contact2_nom'] ?? '',
            $ligne['contact2_telephone'] ? (PhoneFormatter::toReadable($ligne['contact2_telephone']) ?? $ligne['contact2_telephone']) : '',
            $ligne['reference'],
            $ligne['motif'],
            '',
        ];
    }

    public function columnFormats(): array
    {
        return ['E' => NumberFormat::FORMAT_TEXT, 'G' => NumberFormat::FORMAT_TEXT, 'H' => NumberFormat::FORMAT_TEXT];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
        ]];
    }
}
