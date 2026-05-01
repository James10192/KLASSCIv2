<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Exports\ExportableReport;
use App\Exports\RecouvrementExport;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Report Recouvrement quotidien — top-N étudiants à risque enrichis.
 * Consommé par ESBTPRecouvrementController pour preview/PDF/Excel via
 * App\Services\ExportRenderer.
 */
class RecouvrementReport extends ExportableReport
{
    public function __construct(
        private readonly array $rows,
        private readonly array $appliedFilters = [],
        private readonly array $kpis = [],
    ) {}

    public function title(): string
    {
        return 'Recouvrement quotidien';
    }

    public function subtitle(): ?string
    {
        return 'Liste priorisée des étudiants à relancer';
    }

    public function pdfView(): string
    {
        return 'esbtp.comptabilite.recouvrement.pdf';
    }

    public function viewData(): array
    {
        return [
            'rows' => $this->rows,
            'kpis' => $this->kpis,
        ];
    }

    public function excelExport(): FromCollection
    {
        return new RecouvrementExport($this->rows, $this->appliedFilters);
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
        return 'recouvrement_' . now()->format('Ymd_His');
    }
}
