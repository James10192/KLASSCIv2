<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Exports\ExportableReport;
use App\Exports\AccessibilityExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Report cohorte accessibilité — étudiants avec aménagements/handicap.
 * Consommé par ESBTPStudentAccessibilityController pour preview/PDF/Excel.
 */
class AccessibilityReport extends ExportableReport
{
    public function __construct(
        private readonly Collection $rows,
        private readonly array $appliedFilters = [],
        private readonly array $kpis = [],
        private readonly bool $includeFullDescription = false,
    ) {}

    public function title(): string
    {
        return 'Suivi accessibilité étudiants';
    }

    public function subtitle(): ?string
    {
        return 'Aménagements et adaptations pédagogiques';
    }

    public function pdfView(): string
    {
        return 'esbtp.accessibility.pdf';
    }

    public function viewData(): array
    {
        return [
            'rows' => $this->rows,
            'kpis' => $this->kpis,
            'includeFullDescription' => $this->includeFullDescription,
        ];
    }

    public function excelExport(): FromCollection
    {
        return new AccessibilityExport($this->rows, $this->includeFullDescription);
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
        return 'accessibilite_' . now()->format('Ymd_His');
    }
}
