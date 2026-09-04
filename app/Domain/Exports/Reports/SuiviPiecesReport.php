<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Exports\ExportableReport;
use App\Exports\SuiviPiecesExport;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Rapport « pieces manquantes » : c'est le document que le secretariat emporte
 * pour constituer les dossiers a remettre aux ministeres.
 */
class SuiviPiecesReport extends ExportableReport
{
    public function __construct(
        private readonly array $lignes,
        private readonly array $parPiece = [],
        private readonly array $kpis = [],
        private readonly array $filtresAppliques = [],
    ) {}

    public function title(): string
    {
        return 'Pieces manquantes aux dossiers';
    }

    public function subtitle(): ?string
    {
        return 'Qui doit encore fournir quoi';
    }

    public function pdfView(): string
    {
        return 'esbtp.inscriptions.pieces.pdf';
    }

    public function viewData(): array
    {
        return [
            'lignes' => $this->lignes,
            'parPiece' => $this->parPiece,
            'kpis' => $this->kpis,
        ];
    }

    public function excelExport(): FromCollection
    {
        return new SuiviPiecesExport($this->lignes);
    }

    public function filters(): array
    {
        return $this->filtresAppliques;
    }

    public function orientation(): string
    {
        return 'landscape';
    }

    public function filename(): string
    {
        return 'pieces_manquantes_' . now()->format('Ymd_His');
    }
}
