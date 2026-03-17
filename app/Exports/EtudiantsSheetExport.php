<?php

namespace App\Exports;

use Illuminate\Support\Collection;
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

class EtudiantsSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    protected Collection $etudiants;
    protected string $title;
    protected int $rowCounter = 0;

    public function __construct(Collection $etudiants, string $title = 'Étudiants')
    {
        $this->etudiants = $etudiants;
        $this->title = $title;
    }

    public function collection(): Collection
    {
        return $this->etudiants;
    }

    public function headings(): array
    {
        return [
            'N°',
            'Matricule',
            'Nom',
            'Prénoms',
            'Sexe',
            'Date de naissance',
            'Lieu de naissance',
            'Téléphone',
            'Email',
            'Classe',
            'Filière',
            'Niveau',
            'Statut',
            'Année universitaire',
        ];
    }

    public function map($item): array
    {
        $this->rowCounter++;

        $etudiant = $item['etudiant'] ?? $item;
        $inscription = $item['inscription'] ?? null;

        return [
            $this->rowCounter,
            $etudiant->matricule ?? 'N/A',
            $etudiant->nom ?? '',
            $etudiant->prenoms ?? '',
            $etudiant->sexe ?? 'N/A',
            $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'N/A',
            $etudiant->lieu_naissance ?? 'N/A',
            $etudiant->telephone ?? 'N/A',
            $etudiant->email_personnel ?? 'N/A',
            $inscription && $inscription->classe ? $inscription->classe->name : 'N/A',
            $inscription && $inscription->filiere ? $inscription->filiere->name : 'N/A',
            $inscription && $inscription->niveau ? $inscription->niveau->name : 'N/A',
            $etudiant->statut ?? 'N/A',
            $inscription && $inscription->anneeUniversitaire ? $inscription->anneeUniversitaire->name : 'N/A',
        ];
    }

    public function title(): string
    {
        return \Illuminate\Support\Str::limit($this->title, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'N';
                $headerRange = "A1:{$lastCol}1";

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0453CB']]],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(24);

                $lastRow = $sheet->getHighestRow();
                if ($lastRow > 1) {
                    $dataRange = "A2:{$lastCol}{$lastRow}";
                    $sheet->getStyle($dataRange)->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DEE2E6']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    ]);

                    for ($row = 2; $row <= $lastRow; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
                            ]);
                        }
                    }
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter($headerRange);
            },
        ];
    }
}
