<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Analytics\DTOs\PredictionResult;
use App\Domain\Exports\ExportableReport;
use App\Exports\AnalyticsExport;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Report Analytics : agrège CashFlow + DefaultRisk + Anomalies pour
 * export multi-sections (PDF page 1 / Excel multi-sheets).
 */
class AnalyticsReport extends ExportableReport
{
    public function __construct(
        private readonly PredictionResult $cashFlow,
        private readonly PredictionResult $defaultRisk,
        private readonly array $anomalies,
        private readonly array $appliedFilters = [],
    ) {}

    public function title(): string
    {
        return 'Analytics financiers';
    }

    public function subtitle(): ?string
    {
        return 'Synthèse des prédictions cash flow, risque et anomalies — '
            . now()->locale('fr')->translatedFormat('F Y');
    }

    public function pdfView(): string
    {
        return 'esbtp.comptabilite.analytics.pdf';
    }

    public function viewData(): array
    {
        return [
            'cashFlow' => $this->cashFlow,
            'defaultRisk' => $this->defaultRisk,
            'anomalies' => $this->anomalies,
        ];
    }

    public function excelExport(): FromCollection
    {
        return new AnalyticsExport($this->cashFlow, $this->defaultRisk, $this->anomalies, $this->appliedFilters);
    }

    public function filters(): array
    {
        return $this->appliedFilters;
    }

    public function orientation(): string
    {
        return 'portrait';
    }

    public function filename(): string
    {
        return 'analytics_' . now()->format('Ymd_His');
    }
}
