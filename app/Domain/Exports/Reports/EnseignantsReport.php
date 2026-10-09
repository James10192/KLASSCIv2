<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Exports\ExportableReport;
use App\Exports\EnseignantsExport;
use Maatwebsite\Excel\Concerns\FromCollection;

final class EnseignantsReport extends ExportableReport
{
    public function __construct(
        private readonly array $rows,
        private readonly array $appliedFilters,
    ) {}

    public function title(): string { return 'Liste des enseignants'; }
    public function subtitle(): ?string { return 'Annuaire du corps enseignant'; }
    public function pdfView(): string { return 'esbtp.enseignants.export-pdf'; }
    public function orientation(): string { return 'landscape'; }
    public function filters(): array { return $this->appliedFilters; }

    public function viewData(): array
    {
        return ['rows' => $this->rows, 'total' => count($this->rows)];
    }

    public function excelExport(): FromCollection
    {
        return new EnseignantsExport($this->rows);
    }

    public function filename(): string
    {
        return 'liste_enseignants_' . now()->format('Ymd_His');
    }

    // Ne jamais retourner une liste perimee pendant 5 min apres une edition.
    public function cacheKey(): string
    {
        return hash('sha256', json_encode([
            $this->pdfView(), $this->appliedFilters, $this->rows,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
