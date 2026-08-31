<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Services\AcademicPilotageReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AcademicPilotageReportController extends Controller
{
    public function __construct(private readonly AcademicPilotageReportService $reports)
    {
        $this->middleware('auth');
    }

    public function rentree(): View
    {
        return $this->show('rentree', 'Rapport de rentrée');
    }

    public function trimestre(): View
    {
        return $this->show('trimestre', 'Rapport de fin de semestre');
    }

    public function annuel(): View
    {
        return $this->show('annuel', 'Rapport annuel pédagogique');
    }

    public function rentreePdf(): Response
    {
        return $this->pdf('rentree', 'Rapport de rentrée');
    }

    public function trimestrePdf(): Response
    {
        return $this->pdf('trimestre', 'Rapport de fin de semestre');
    }

    public function annuelPdf(): Response
    {
        return $this->pdf('annuel', 'Rapport annuel pédagogique');
    }

    private function show(string $kind, string $title): View
    {
        abort_unless(auth()->user()?->can('reports.academic.'.$kind), 403);

        return view('esbtp.rapports.academique', [
            'title' => $title,
            'kind' => $kind,
            'report' => $this->reports->build($kind),
        ]);
    }

    public function rentreePdfPreview(): Response
    {
        return $this->pdf('rentree', 'Rapport de rentrée', inline: true);
    }

    public function trimestrePdfPreview(): Response
    {
        return $this->pdf('trimestre', 'Rapport de fin de semestre', inline: true);
    }

    public function annuelPdfPreview(): Response
    {
        return $this->pdf('annuel', 'Rapport annuel pédagogique', inline: true);
    }

    /**
     * Construit le PDF une seule fois pour les deux usages : téléchargement et
     * aperçu inline (cf. .claude/rules/exports-pdf-excel.md, chaque export doit
     * offrir un aperçu avant de consommer une impression).
     */
    private function pdf(string $kind, string $title, bool $inline = false): Response
    {
        abort_unless(auth()->user()?->can('reports.academic.'.$kind), 403);

        $pdf = Pdf::loadView('esbtp.rapports.academique-pdf', [
            'title' => $title,
            'kind' => $kind,
            'report' => $this->reports->build($kind),
            'school' => SettingsHelper::getSchoolInfo(),
        ])->setPaper('a4', 'portrait');

        $filename = 'rapport-'.$kind.'-'.now()->format('Ymd').'.pdf';

        if (!$inline) {
            return $pdf->download($filename);
        }

        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}