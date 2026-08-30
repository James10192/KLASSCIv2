<?php

namespace App\Exports;

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
        $ecole = $this->payload['ecole']['name'] ?? 'Établissement';
        $rows = [
            [$ecole],
            ['ANNEE UNIVERSITAIRE', $this->payload['annee'] ?? ''],
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
        $base = [
            "N° d'ordre", 'IP', 'Nom', 'Prénoms', 'Date de naissance', 'Lieu de naissance',
            'Sexe', 'Nationalité', 'Moy S1', 'Crédits S1', 'Moy S2', 'Crédits S2',
            'Moyenne annuelle', 'Total crédits', 'Décision de fin d\'année',
        ];
        foreach ($this->payload['ues_header'] as $ue) {
            $base[] = ($ue['code'] ?? '').' '.($ue['name'] ?? '');
            $base[] = 'Moyenne';
            $base[] = 'Statut';
            $base[] = 'Crédits';
        }

        return $base;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:A4')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A6:Z6')->getFont()->setBold(true);
            },
        ];
    }
}
