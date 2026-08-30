<?php

namespace App\Http\Controllers;

use App\Exports\LmdPvAnnuelExport;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPLMDJury;
use App\Services\LMD\LmdPvAnnuelAssembler;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ESBTPLMDPvExportController extends Controller
{
    use Concerns\RespondsWithInlinePdf;

    public function __construct(private LmdPvAnnuelAssembler $assembler) {}

    public function excel(ESBTPLMDJury $jury)
    {
        $payload = $this->assembler->forJury($jury);

        return Excel::download(new LmdPvAnnuelExport($payload), $this->basename($jury).'.xlsx');
    }

    public function pdf(Request $request, ESBTPLMDJury $jury)
    {
        $payload = $this->assembler->forJury($jury);
        $school = SettingsHelper::getSchoolInfo();
        $pdf = Pdf::loadView('esbtp.lmd.jurys.pdf.pv-annuel', compact('payload', 'school'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'dpi' => 120,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
            ]);

        return $this->respondWithPdf($pdf, $this->basename($jury).'.pdf', $request);
    }

    private function basename(ESBTPLMDJury $jury): string
    {
        return 'pv-annuel-'.($jury->pv_numero ?? $jury->id);
    }
}
