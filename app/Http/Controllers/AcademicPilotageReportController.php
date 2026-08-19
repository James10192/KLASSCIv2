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
        return $this->show('trimestre', 'Rapport de fin de trimestre');
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
        return $this->pdf('trimestre', 'Rapport de fin de trimestre');
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

    private function pdf(string $kind, string $title): Response
    {
        abort_unless(auth()->user()?->can('reports.academic.'.$kind), 403);

        $pdf = Pdf::loadView('esbtp.rapports.academique-pdf', [
            'title' => $title,
            'kind' => $kind,
            'report' => $this->reports->build($kind),
            'school' => SettingsHelper::getSchoolInfo(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('rapport-'.$kind.'-'.now()->format('Ymd').'.pdf');
    }
}