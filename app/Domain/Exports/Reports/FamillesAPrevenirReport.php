<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Exports\ExportableReport;
use App\Exports\FamillesAPrevenirExport;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Liste d'appel des familles qu'aucun courriel n'a prevenues de leur rendez-vous.
 * Consommee par ESBTPRendezVousExportController via ExportRenderer.
 */
class FamillesAPrevenirReport extends ExportableReport
{
    public const PDF_MAX_ROWS = 1000;

    public const EXCEL_MAX_ROWS = 50000;

    /** @param  list<array<string, mixed>>  $lignes */
    public function __construct(private readonly array $lignes)
    {
    }

    public function title(): string
    {
        return 'Familles à prévenir';
    }

    public function subtitle(): ?string
    {
        return 'Rendez-vous d\'inscription à venir sans convocation reçue par e-mail — à appeler';
    }

    public function pdfView(): string
    {
        return 'esbtp.rendez-vous.pdf.familles-a-prevenir';
    }

    public function viewData(): array
    {
        return ['lignes' => $this->lignes];
    }

    public function excelExport(): FromCollection
    {
        return new FamillesAPrevenirExport($this->lignes);
    }

    public function orientation(): string
    {
        return 'landscape';
    }

    public function filename(): string
    {
        return 'familles-a-prevenir_'.now()->format('Ymd_His');
    }

    /**
     * La liste change sans qu'aucun filtre ne bouge — une famille notee
     * prevenue par telephone en sort, une reprogrammation en fait entrer une :
     * la cle de cache porte donc le contenu, sinon le PDF servirait pendant
     * cinq minutes une liste perimee.
     */
    public function cacheKey(): string
    {
        return hash('sha256', $this->pdfView().'|'.json_encode($this->lignes, JSON_UNESCAPED_UNICODE));
    }
}
