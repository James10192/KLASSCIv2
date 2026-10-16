<?php

namespace App\Http\Controllers;

use App\Domain\Exports\Reports\ClasseEffectifsReport;
use App\Http\Requests\Reports\ClasseEffectifsFilterRequest;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Services\ExportRenderer;
use App\Services\Reports\ClasseEffectifsReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ClasseEffectifsReportController extends Controller
{
    public function __construct(private readonly ClasseEffectifsReportService $reports)
    {
    }

    public function index(ClasseEffectifsFilterRequest $request): View
    {
        $filters = $request->filters();

        return view('esbtp.rapports.effectifs', [
            'report' => $this->reports->build($filters),
            'filters' => $filters,
            'activeFilters' => $this->filterLabels($filters),
            'annees' => ESBTPAnneeUniversitaire::query()->orderByDesc('start_date')->get(['id', 'name']),
            'filieres' => ESBTPFiliere::query()->orderBy('name')->get(['id', 'name']),
            'niveaux' => ESBTPNiveauEtude::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function data(ClasseEffectifsFilterRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $report = $this->reports->build($filters);
        $activeFilters = $this->filterLabels($filters);

        return response()->json([
            'success' => true,
            'html' => view('esbtp.rapports.partials.effectifs-resultats', compact('report', 'activeFilters'))->render(),
            'eligible_count' => $report['eligible_count'],
            'retained_count' => $report['retained_count'],
            'unique_students' => $report['unique_students'],
            'sum_class_enrollments' => $report['sum_class_enrollments'],
        ]);
    }

    public function preview(ClasseEffectifsFilterRequest $request, ExportRenderer $renderer): Response
    {
        abort_unless($request->user()->can('reports.generate'), 403);

        return $renderer->pdfPreview($this->exportReport($request));
    }

    public function download(ClasseEffectifsFilterRequest $request, ExportRenderer $renderer): Response
    {
        abort_unless($request->user()->can('reports.generate'), 403);

        return $renderer->pdfDownload($this->exportReport($request));
    }

    private function exportReport(ClasseEffectifsFilterRequest $request): ClasseEffectifsReport
    {
        $filters = $request->filters();
        $report = $this->reports->build($filters, paginate: false);
        abort_if($report['retained_count'] > ClasseEffectifsReportService::PDF_MAX_ROWS, 422,
            'Le PDF dépasse 1 000 classes. Réduisez le nombre de classes ou affinez les filtres.');

        return new ClasseEffectifsReport($report, $this->filterLabels($filters));
    }

    /** @return array<string, string> */
    private function filterLabels(array $filters): array
    {
        $year = ($filters['annee_universitaire_id']
            ? ESBTPAnneeUniversitaire::find($filters['annee_universitaire_id'])?->name
            : null) ?? 'Non définie';
        $labels = [
            'Année universitaire' => $year,
            'Minimum d’inscrits' => (string) $filters['min_inscrits'],
            'Nombre de classes' => $filters['nombre_classes'] === 'all' ? 'Toutes' : (string) $filters['nombre_classes'],
            'Tri' => $filters['sort'] === 'nom_asc' ? 'Nom de classe' : 'Effectif décroissant',
        ];
        if ($filters['filiere_id']) {
            $labels['Filière / mention'] = ESBTPFiliere::find($filters['filiere_id'])?->name ?? '—';
        }
        if ($filters['niveau_id']) {
            $labels['Niveau'] = ESBTPNiveauEtude::find($filters['niveau_id'])?->name ?? '—';
        }

        return $labels;
    }
}
