<?php

namespace App\Http\Controllers;

use App\Domain\Exports\Reports\PvSemestreDetailleReport;
use App\Models\ESBTPLMDJury;
use App\Services\ExportRenderer;
use App\Services\LMD\PvSemestreDetailleAssembler;
use Illuminate\Http\Request;

/**
 * Export du PV de délibération détaillé d'un semestre (tableur et PDF).
 *
 * Même garde que le PV annuel : `lmd.pv.export` ouvre le format, l'appartenance
 * au jury ouvre le contenu.
 */
class ESBTPLMDPvSemestreController extends Controller
{
    public function __construct(
        private PvSemestreDetailleAssembler $assembler,
        private ExportRenderer $renderer,
    ) {}

    public function excel(ESBTPLMDJury $jury)
    {
        return $this->renderer->excelDownload($this->report($jury));
    }

    public function pdf(Request $request, ESBTPLMDJury $jury)
    {
        $report = $this->report($jury);
        abort_if($report->depasseLeVolumePdf(), 422, sprintf(
            'Ce PV compte plus de %d étudiants : téléchargez-le en Excel.', PvSemestreDetailleReport::PDF_MAX_ROWS,
        ));

        return $request->boolean('inline')
            ? $this->renderer->pdfPreview($report)
            : $this->renderer->pdfDownload($report);
    }

    private function report(ESBTPLMDJury $jury): PvSemestreDetailleReport
    {
        $jury->assertConsultablePar(auth()->user());
        abort_if(! $jury->semestre, 422, 'Ce jury ne porte pas de semestre : le PV détaillé se tire semestre par semestre.');

        return new PvSemestreDetailleReport($this->assembler->forJury($jury), $jury->id);
    }
}
