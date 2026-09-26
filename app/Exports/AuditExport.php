<?php

namespace App\Exports;

use App\Domain\Audit\LigneDuJournal;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Le journal d'audit en tableur, ligne pour ligne comme a l'ecran : la meme
 * phrase, les memes reperes, le meme changement (JournalLisible), suivis de
 * la trace technique (entree, evenement, IP, navigateur, ecran).
 */
class AuditExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /** @param  list<LigneDuJournal>  $lignes */
    public function __construct(private readonly array $lignes)
    {
    }

    public function collection()
    {
        return collect($this->lignes);
    }

    public function headings(): array
    {
        return ['Date et heure', 'Qui', 'Rôle', 'Ce qui s\'est passé', 'Repères', 'Changement', 'À regarder',
            'N° d\'entrée', 'Événement', 'Adresse IP', 'Navigateur', 'Écran'];
    }

    /** @param  LigneDuJournal  $ligne */
    public function map($ligne): array
    {
        return [
            $ligne->quand->format('d/m/Y H:i'),
            $ligne->acteur,
            $ligne->role ?? '',
            $ligne->phrase(),
            implode(' · ', $ligne->objet->reperes),
            $ligne->changement ?? '',
            implode(', ', $ligne->motifs),
            // La trace technique, conservee pour la valeur probante de l'export.
            $ligne->id,
            $ligne->evenement ?? '',
            $ligne->ip ?? '',
            \App\Domain\Audit\JournalLisible::navigateur($ligne->agent) ?? '',
            $ligne->url ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }
}
