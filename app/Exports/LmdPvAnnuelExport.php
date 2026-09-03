<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class LmdPvAnnuelExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(private array $payload) {}

    public function title(): string
    {
        return 'PV annuel';
    }

    public function array(): array
    {
        $rows = [
            [$this->payload['ecole']['name'] ?? 'Établissement'],
            ['ANNÉE UNIVERSITAIRE', $this->payload['annee'] ?? ''],
            ['PARCOURS', $this->payload['parcours'] ?? ''],
            ['NIVEAU', $this->payload['niveau'] ?? ''],
            [],
            $this->headings(),
        ];

        foreach ($this->payload['rows'] as $row) {
            $line = [
                $row['ordre'],
                $row['matricule'],
                $row['nom'],
                $row['prenoms'],
                $row['date_naissance'],
                $row['lieu_naissance'],
                $row['sexe'],
                $row['nationalite'],
                $row['moy_s1'],
                $row['credits_s1'],
                $row['moy_s2'],
                $row['credits_s2'],
                $row['moy_annuelle'],
                $row['credits_annuels'],
                $row['decision'],
            ];
            foreach ($row['ues'] as $ue) {
                $line[] = $ue['code'];
                $line[] = $ue['moyenne'];
                $line[] = $ue['statut'];
                $line[] = $ue['credit'];
            }
            $rows[] = $line;
        }

        return $rows;
    }

    private function headings(): array
    {
        // Les deux semestres de l'annee ne sont pas toujours 1 et 2 : une Licence 2
        // porte les semestres 3 et 4, une Licence 3 les semestres 5 et 6.
        $premier = (int) ($this->payload['semestres']['premier'] ?? 1);
        $second = (int) ($this->payload['semestres']['second'] ?? 2);

        $base = [
            "N° d'ordre", 'IP', 'Nom', 'Prénoms', 'Date de naissance', 'Lieu de naissance',
            'Sexe', 'Nationalité',
            'Moy S'.$premier, 'Crédits S'.$premier,
            'Moy S'.$second, 'Crédits S'.$second,
            'Moyenne annuelle', 'Total crédits', 'Décision de fin d\'année',
        ];
        foreach ($this->payload['ues_header'] as $ue) {
            $base[] = trim(($ue['code'] ?? '').' '.($ue['name'] ?? ''));
            $base[] = 'Moyenne';
            $base[] = 'Statut';
            $base[] = 'Crédits';
        }

        return $base;
    }

    public function registerEvents(): array
    {
        $rgb = $this->hexToRgb($this->payload['primary'] ?? '#0453cb');

        return [
            AfterSheet::class => function (AfterSheet $event) use ($rgb) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $sheet->mergeCells('B1:'.$lastCol.'1');
                $sheet->getRowDimension(1)->setRowHeight(36);
                $sheet->getStyle('A1:'.$lastCol.'1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle('A2:A4')->getFont()->setBold(true);
                $sheet->getStyle('A6:'.$lastCol.'6')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]],
                ]);

                $binary = $this->payload['logo_binary'] ?? null;
                if (is_string($binary) && $binary !== '') {
                    $gd = @imagecreatefromstring($binary);
                    if ($gd !== false) {
                        $drawing = new MemoryDrawing();
                        $drawing->setName('Logo');
                        $drawing->setImageResource($gd);
                        $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
                        $drawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);
                        $drawing->setHeight(40);
                        $drawing->setCoordinates('A1');
                        $drawing->setOffsetX(6);
                        $drawing->setOffsetY(4);
                        $drawing->setWorksheet($sheet);
                    }
                }
            },
        ];
    }

    private function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return strlen($hex) === 6 ? strtoupper($hex) : '0453CB';
    }
}
