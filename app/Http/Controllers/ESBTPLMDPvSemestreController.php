<?php

namespace App\Http\Controllers;

use App\Exports\LmdPvSemestreDetailleExport;
use App\Models\ESBTPLMDJury;
use App\Services\LMD\PvSemestreDetailleAssembler;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Export du PV de délibération détaillé d'un semestre (tableur et PDF).
 *
 * Même garde que le PV annuel : `lmd.pv.export` ouvre le format, l'appartenance
 * au jury ouvre le contenu.
 */
class ESBTPLMDPvSemestreController extends Controller
{
    use Concerns\RespondsWithInlinePdf;

    public function __construct(private PvSemestreDetailleAssembler $assembler) {}

    public function excel(ESBTPLMDJury $jury)
    {
        $payload = $this->payload($jury);

        return Excel::download(new LmdPvSemestreDetailleExport($payload), \App\Support\NomDeFichier::sur($this->basename($jury).'.xlsx'));
    }

    public function pdf(Request $request, ESBTPLMDJury $jury)
    {
        $payload = $this->payload($jury);
        $pdf = Pdf::loadView('esbtp.lmd.jurys.pdf.pv-semestre-detaille', compact('payload'))
            ->setPaper('a3', 'landscape')
            ->setOptions([
                'dpi' => 120,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
            ]);

        return $this->respondWithPdf($pdf, $this->basename($jury).'.pdf', $request);
    }

    private function payload(ESBTPLMDJury $jury): array
    {
        $jury->assertConsultablePar(auth()->user());
        abort_if(! $jury->semestre, 422, 'Ce jury ne porte pas de semestre : le PV détaillé se tire semestre par semestre.');

        return $this->assembler->forJury($jury);
    }

    private function basename(ESBTPLMDJury $jury): string
    {
        return 'pv-deliberation-s'.$jury->semestre.'-'.($jury->pv_numero ?? $jury->id);
    }
}
