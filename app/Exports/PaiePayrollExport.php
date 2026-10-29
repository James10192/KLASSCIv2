<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export Excel de l'état de paie des enseignants. Colonnes alignées avec le PDF.
 */
class PaiePayrollExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    /**
     * @param  array<int, array>  $rows
     * @param  array<string, string>  $statutLabels
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly array $rows,
        private readonly array $statutLabels = [],
        private readonly array $filters = [],
    ) {}

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return ['Enseignant', 'Mois', 'Heures réalisées', 'Base (FCFA)', 'Retenues (FCFA)', 'Net à payer (FCFA)', 'Statut'];
    }

    /** @param array $row */
    public function map($row): array
    {
        return [
            $row['name'] ?? '',
            (int) ($row['nb_mois'] ?? 1),
            (float) ($row['heures'] ?? 0),
            (float) ($row['base'] ?? 0),
            (float) ($row['retenues'] ?? 0),
            (float) ($row['net'] ?? 0),
            $this->statutLabels[$row['statut'] ?? ''] ?? ($row['statut'] ?? ''),
        ];
    }

    public function title(): string
    {
        return 'Paie enseignants';
    }
}
