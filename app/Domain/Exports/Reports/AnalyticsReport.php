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
    /**
     * @param  array<string, array{expected: float, paid: float, gap: float, gap_ratio: float}>  $recouvrementGaps
     */
    public function __construct(
        private readonly PredictionResult $cashFlow,
        private readonly PredictionResult $defaultRisk,
        private readonly array $anomalies,
        private readonly array $appliedFilters = [],
        private readonly array $recouvrementGaps = [],
        private readonly string $echeancierMode = \App\Services\EcheancierReadinessService::MODE_CONFIGURED,
        private readonly ?string $echeancierNote = null,
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
            'recouvrementGaps' => $this->recouvrementGaps,
            'echeancierMode' => $this->echeancierMode,
            'echeancierNote' => $this->echeancierNote,
        ];
    }

    public function excelExport(): FromCollection
    {
        return new AnalyticsExport(
            $this->cashFlow,
            $this->defaultRisk,
            $this->anomalies,
            $this->appliedFilters,
            $this->recouvrementGaps,
        );
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
