<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Domain\Exports\ExportableReport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * PV de Réconciliation Caisse — document officiel signable, archivable 10 ans (norme OHADA).
 *
 * Consommé par ESBTPReconciliationController::exportPv via App\Services\ExportRenderer.
 * Pas de version Excel utile (document officiel = PDF signable). L'interface impose
 * excelExport() mais on retourne un FromCollection vide (silent OK).
 */
class ReconciliationPVReport extends ExportableReport
{
    public function __construct(private readonly ReconciliationSession $session) {}

    public function title(): string
    {
        return 'PV de Réconciliation Caisse';
    }

    public function subtitle(): ?string
    {
        return 'Session ' . $this->session->code;
    }

    public function pdfView(): string
    {
        return 'esbtp.comptabilite.reconciliation.pdf';
    }

    public function viewData(): array
    {
        $this->session->loadMissing([
            'opener', 'reviewer', 'approver', 'closer',
            'cashCounts', 'discrepancies',
        ]);
        return [
            'session' => $this->session,
            'cashCounts' => $this->session->cashCounts,
            'discrepancies' => $this->session->discrepancies,
            'totalEcart' => $this->session->totalEcart(),
        ];
    }

    /**
     * Pas de version Excel utile pour un PV officiel. Collection vide.
     */
    public function excelExport(): FromCollection
    {
        return new class implements FromCollection {
            public function collection(): Collection
            {
                return collect();
            }
        };
    }

    public function filters(): array
    {
        return [
            'Période' => optional($this->session->period_start)->format('d/m/Y')
                . ($this->session->period_end != $this->session->period_start
                    ? ' → ' . optional($this->session->period_end)->format('d/m/Y') : ''),
            'Fréquence' => ucfirst($this->session->frequency),
            'Statut' => $this->session->status->label(),
        ];
    }

    public function orientation(): string
    {
        return 'portrait';
    }

    public function filename(): string
    {
        return 'PV_' . $this->session->code . '_' . now()->format('Ymd');
    }
}
