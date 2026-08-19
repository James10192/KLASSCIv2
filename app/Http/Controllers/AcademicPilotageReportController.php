<?php

namespace App\Http\Controllers;

use App\Services\AcademicPilotageReportService;
use Illuminate\View\View;

class AcademicPilotageReportController extends Controller
{
    public function __construct(private readonly AcademicPilotageReportService $reports)
    {
        $this->middleware('auth');
    }

    public function rentree(): View
    {
        abort_unless(auth()->user()?->can('reports.academic.rentree'), 403);

        return view('esbtp.rapports.academique', [
            'title' => 'Rapport de rentree',
            'report' => $this->reports->build('rentree'),
        ]);
    }

    public function trimestre(): View
    {
        abort_unless(auth()->user()?->can('reports.academic.trimestre'), 403);

        return view('esbtp.rapports.academique', [
            'title' => 'Rapport de fin de trimestre',
            'report' => $this->reports->build('trimestre'),
        ]);
    }

    public function annuel(): View
    {
        abort_unless(auth()->user()?->can('reports.academic.annuel'), 403);

        return view('esbtp.rapports.academique', [
            'title' => 'Rapport annuel pedagogique',
            'report' => $this->reports->build('annuel'),
        ]);
    }
}