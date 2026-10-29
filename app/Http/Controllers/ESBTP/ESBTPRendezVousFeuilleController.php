<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\Exports\Reports\FeuilleRendezVousReport;
use App\Http\Controllers\Controller;
use App\Services\ExportRenderer;
use App\Services\RendezVous\FeuilleRendezVous;
use Illuminate\Http\Request;

/**
 * Feuille de suivi imprimable : la semaine affichee (`debut`) ou une journee
 * (`jour`). Apercu dans un nouvel onglet, PDF, Excel.
 */
class ESBTPRendezVousFeuilleController extends Controller
{
    public function __construct(
        private readonly FeuilleRendezVous $feuille,
        private readonly ExportRenderer $renderer,
    ) {
    }

    public function apercu(Request $request)
    {
        return $this->renderer->pdfPreview($this->rapport($request, FeuilleRendezVousReport::PDF_MAX_ROWS));
    }

    public function pdf(Request $request)
    {
        return $this->renderer->pdfDownload($this->rapport($request, FeuilleRendezVousReport::PDF_MAX_ROWS));
    }

    public function excel(Request $request)
    {
        return $this->renderer->excelDownload($this->rapport($request, FeuilleRendezVousReport::EXCEL_MAX_ROWS));
    }

    private function rapport(Request $request, int $maximum): FeuilleRendezVousReport
    {
        $p = $this->feuille->periode(
            is_string($request->query('debut')) ? $request->query('debut') : null,
            is_string($request->query('jour')) ? $request->query('jour') : null,
        );

        $n = $this->feuille->compter($p['debut'], $p['fin']);
        abort_if($n > $maximum, 422, sprintf(
            '%d rendez-vous sur la période : au-delà de %d lignes, imprimez jour par jour ou téléchargez la version Excel.',
            $n, $maximum
        ));

        return new FeuilleRendezVousReport($this->feuille->lignes($p['debut'], $p['fin']), $p['debut'], $p['fin'], $p['jourSeul']);
    }
}
