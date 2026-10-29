<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\Exports\Reports\FamillesAPrevenirReport;
use App\Http\Controllers\Controller;
use App\Services\ExportRenderer;
use App\Services\RendezVous\FamillesAPrevenirRdv;

/**
 * Export des familles a prevenir par telephone : apercu, PDF, Excel.
 */
class ESBTPRendezVousExportController extends Controller
{
    public function __construct(
        private readonly FamillesAPrevenirRdv $familles,
        private readonly ExportRenderer $renderer,
    ) {
    }

    public function apercu()
    {
        return $this->renderer->pdfPreview($this->rapport(FamillesAPrevenirReport::PDF_MAX_ROWS));
    }

    public function pdf()
    {
        return $this->renderer->pdfDownload($this->rapport(FamillesAPrevenirReport::PDF_MAX_ROWS));
    }

    public function excel()
    {
        return $this->renderer->excelDownload($this->rapport(FamillesAPrevenirReport::EXCEL_MAX_ROWS));
    }

    private function rapport(int $maximum): FamillesAPrevenirReport
    {
        $n = $this->familles->compter();
        abort_if($n > $maximum, 422, sprintf(
            '%d familles à prévenir : au-delà de %d lignes, ce format devient illisible. Téléchargez la version Excel.',
            $n, $maximum
        ));

        return new FamillesAPrevenirReport($this->familles->lignes());
    }
}
