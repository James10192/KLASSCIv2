<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Exports\ExportableReport;
use App\Exports\PaiePayrollExport;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * État de paie des enseignants pour une période — récap « ce qu'on doit verser ».
 * Consommé par ESBTPSalaireController pour preview/PDF/Excel via ExportRenderer.
 */
class PaiePayrollReport extends ExportableReport
{
    public function __construct(
        private readonly array $rows,
        private readonly array $kpis = [],
        private readonly array $appliedFilters = [],
        private readonly string $periodLabel = '',
        private readonly array $statutLabels = [],
    ) {}

    public function title(): string
    {
        return 'État de paie des enseignants';
    }

    public function subtitle(): ?string
    {
        return 'Période : ' . $this->periodLabel;
    }

    public function pdfView(): string
    {
        return 'esbtp.comptabilite.salaires.pdf';
    }

    public function viewData(): array
    {
        return [
            'rows'         => $this->rows,
            'kpis'         => $this->kpis,
            'periodLabel'  => $this->periodLabel,
            'statutLabels' => $this->statutLabels,
        ];
    }

    public function excelExport(): FromCollection
    {
        return new PaiePayrollExport($this->rows, $this->statutLabels, $this->appliedFilters);
    }

    public function filters(): array
    {
        return $this->appliedFilters;
    }

    public function orientation(): string
    {
        return 'landscape';
    }

    public function filename(): string
    {
        return 'etat_paie_' . now()->format('Ymd_His');
    }
}
