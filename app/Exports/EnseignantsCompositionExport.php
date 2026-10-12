<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Le meme tableau compose pour Excel et PDF ; rubriques en lignes fusionnees
 * plutot qu'une colonne repetee inutilement pour chaque professeur.
 */
final class EnseignantsCompositionExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    private array $lignes = [];
    private array $rangsRubriques = [];

    public function __construct(private readonly array $composition)
    {
        $index = 2; // Ligne 1 : titres de colonnes.
        $avecRubriques = $composition['regroupement'] !== 'aucun';
        foreach ($composition['groupes'] as $groupe) {
            if ($avecRubriques) {
                $this->rangsRubriques[] = $index++;
                $this->lignes[] = array_pad([$groupe['titre'].' — '.count($groupe['lignes']).' ligne(s)'],
                    count($composition['colonnes']), '');
            }
            foreach ($groupe['lignes'] as $row) {
                $this->lignes[] = array_values($row);
                $index++;
            }
        }
    }

    public function headings(): array
    {
        return array_values($this->composition['entetes']);
    }

    public function collection(): Collection
    {
        return collect($this->lignes)->map(fn ($ligne) => array_map(function ($valeur) {
            if (! is_string($valeur)) return $valeur;
            return preg_match('/^[=+@\-\t\r]/u', $valeur) ? "'".$valeur : $valeur;
        }, $ligne));
    }

    public function columnFormats(): array
    {
        $formats = [];
        foreach (array_keys($this->composition['entetes']) as $index => $key) {
            if (! in_array($key, ['S1', 'S2', 'total', 'nb_classes', 'enseignants_saisis'], true)) {
                $formats[Coordinate::stringFromColumnIndex($index + 1)] = NumberFormat::FORMAT_TEXT;
            }
        }
        return $formats;
    }

    public function styles(Worksheet $sheet)
    {
        $count = count($this->composition['colonnes']);
        $last = Coordinate::stringFromColumnIndex($count);
        $lastRow = max(2, count($this->lignes) + 1);
        $sheet->freezePane('A2');
        $sheet->getStyle('A1:'.$last.$lastRow)->getAlignment()->setWrapText(true);
        if ($this->rangsRubriques === []) $sheet->setAutoFilter('A1:'.$last.$lastRow);
        foreach (array_keys($this->composition['entetes']) as $index => $key) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->getColumnDimension($letter)->setWidth(match ($key) {
                'nom', 'classes', 'email' => 40,
                'matiere', 'filiere', 'specialisation' => 35,
                default => 24,
            });
        }
        foreach ($this->rangsRubriques as $row) {
            if ($count > 1) $sheet->mergeCells('A'.$row.':'.$last.$row);
            $sheet->getStyle('A'.$row.':'.$last.$row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0453CB']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
            ]);
        }
        return [1 => [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
        ]];
    }
}
