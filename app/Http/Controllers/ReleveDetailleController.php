<?php

namespace App\Http\Controllers;

use App\Domain\Exports\Reports\ReleveDetailleReport;
use App\Domain\Releves\ReleveDetailleAssembler;
use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDJury;
use App\Services\ExportRenderer;
use Illuminate\Http\Request;

/**
 * Relevé de notes détaillé d'une classe, document de travail du jury :
 * depuis un jury LMD, ou pour une classe BTS et un semestre.
 */
class ReleveDetailleController extends Controller
{
    public function __construct(private ReleveDetailleAssembler $assembler, private ExportRenderer $renderer) {}

    public function jury(Request $request, ESBTPLMDJury $jury)
    {
        $jury->assertConsultablePar(auth()->user());
        abort_if(! $jury->semestre, 422, 'Ce jury ne porte pas de semestre : le relevé se tire semestre par semestre.');

        return $this->rendre($request, new ReleveDetailleReport($this->assembler->pourJuryLmd($jury), 'jury-'.$jury->id));
    }

    public function classe(Request $request, ESBTPClasse $classe)
    {
        $v = $request->validate([
            'annee_universitaire_id' => ['required', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'periode' => ['required', 'in:semestre1,semestre2'],
        ]);
        abort_if(($classe->systeme_academique ?? '') === 'LMD', 422, 'Classe LMD : tirez le relevé depuis le jury du semestre.');

        $payload = $this->assembler->pourClasseBts($classe, (int) $v['annee_universitaire_id'], $v['periode']);

        return $this->rendre($request, new ReleveDetailleReport($payload, 'classe-'.$classe->id.'-'.$v['periode']));
    }

    private function rendre(Request $request, ReleveDetailleReport $report)
    {
        abort_if($report->depasseLeVolumePdf(), 422, sprintf('Plus de %d étudiants : tirez le relevé par groupe.', ReleveDetailleReport::PDF_MAX_ETUDIANTS));

        if ($request->query('format') === 'excel') {
            return $this->renderer->excelDownload($report);
        }

        return $request->boolean('inline') ? $this->renderer->pdfPreview($report) : $this->renderer->pdfDownload($report);
    }
}
