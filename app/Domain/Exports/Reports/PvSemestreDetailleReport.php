<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Exports\ExportableReport;
use App\Exports\LmdPvSemestreDetailleExport;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Le PV de délibération détaillé d'un semestre, tel que l'assemble
 * PvSemestreDetailleAssembler.
 *
 * La clé de cache porte le contenu lui-même : un PV téléchargé deux fois dans
 * les cinq minutes ne doit jamais servir l'ancienne version d'une décision
 * que le jury vient de modifier.
 */
class PvSemestreDetailleReport extends ExportableReport
{
    /** Au-delà, une feuille A3 devient illisible et DomPDF sature. */
    public const PDF_MAX_ROWS = 300;

    public function __construct(private array $payload, private int $juryId) {}

    public function title(): string
    {
        return 'Procès-verbal de délibération';
    }

    public function subtitle(): ?string
    {
        return trim('Semestre '.$this->payload['semestre'].' · '.($this->payload['annee'] ?? ''), ' ·');
    }

    public function pdfView(): string
    {
        return 'esbtp.lmd.jurys.pdf.pv-semestre-detaille';
    }

    public function viewData(): array
    {
        return ['payload' => $this->payload];
    }

    public function excelExport(): FromCollection
    {
        return new LmdPvSemestreDetailleExport($this->payload);
    }

    public function paper(): string
    {
        return 'A3';
    }

    public function orientation(): string
    {
        return 'landscape';
    }

    public function filename(): string
    {
        return 'pv-deliberation-s'.$this->payload['semestre'].'-jury-'.$this->juryId;
    }

    public function cacheKey(): string
    {
        return hash('sha256', $this->pdfView().'|'.$this->juryId.'|'.json_encode($this->payload));
    }

    public function depasseLeVolumePdf(): bool
    {
        return count($this->payload['rows']) > self::PDF_MAX_ROWS;
    }
}
