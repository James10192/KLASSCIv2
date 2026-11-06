<?php

namespace App\Http\Controllers;

use App\Domain\Comptabilite\SuiviClasses\SituationClassesService;
use App\Domain\Exports\Reports\SituationClassesReport;
use App\Http\Requests\Comptabilite\SuiviClassesFilterRequest;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPNiveauEtude;
use App\Services\ExportRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ESBTPSuiviClassesController extends Controller
{
    public function __construct(private readonly SituationClassesService $situations)
    {
        $this->middleware(['auth', 'comptabilite.access', 'can:module.comptabilite.access', 'can:comptabilite.suivi_classes.view']);
    }

    public function index(SuiviClassesFilterRequest $request): View
    {
        return view('esbtp.comptabilite.suivi-classes.index', [
            'rapport' => $this->situations->build($request->filters()),
            'filtres' => $request->filters(),
            'annees' => ESBTPAnneeUniversitaire::orderByDesc('start_date')->get(),
            'filieres' => ESBTPFiliere::orderBy('name')->get(),
            'niveaux' => ESBTPNiveauEtude::orderBy('name')->get(),
            'classesOptions' => ESBTPClasse::orderBy('name')->get(['id', 'name']),
            'categories' => ESBTPFraisCategory::where('is_active', true)->orderBy('nom')->get(),
            'systemes' => ESBTPClasse::whereNotNull('systeme_academique')->distinct()->pluck('systeme_academique'),
        ]);
    }

    public function data(SuiviClassesFilterRequest $request): JsonResponse
    {
        $rapport = $this->situations->build($request->filters());
        return response()->json([
            'html' => view('esbtp.comptabilite.suivi-classes.partials.resultats', compact('rapport'))->render(),
            'totaux' => $rapport['totaux'],
            'categorie' => $rapport['filtered_category'],
        ]);
    }

    public function details(SuiviClassesFilterRequest $request, ESBTPClasse $classe): JsonResponse
    {
        abort_unless($request->user()?->can('comptabilite.suivi_classes.view_details'), 403);
        $filters = array_merge($request->filters(), ['classe' => (int) $classe->id, 'page' => 1]);
        $rapport = $this->situations->build($filters, avecEtudiants: true, pagination: false);
        $ligne = $rapport['classes']->first();

        return response()->json([
            'html' => view('esbtp.comptabilite.suivi-classes.partials.details', [
                'ligne' => $ligne, 'categorieFiltre' => $rapport['filtered_category'],
            ])->render(),
        ]);
    }

    public function preview(SuiviClassesFilterRequest $request, ExportRenderer $renderer): Response
    {
        $this->authorizeExport($request);
        return $renderer->pdfPreview($this->report($request, SituationClassesService::PDF_MAX_ROWS));
    }

    public function downloadPdf(SuiviClassesFilterRequest $request, ExportRenderer $renderer): Response
    {
        $this->authorizeExport($request);
        return $renderer->pdfDownload($this->report($request, SituationClassesService::PDF_MAX_ROWS));
    }

    public function downloadExcel(SuiviClassesFilterRequest $request, ExportRenderer $renderer): BinaryFileResponse
    {
        $this->authorizeExport($request);
        return $renderer->excelDownload($this->report($request, SituationClassesService::EXCEL_MAX_ROWS));
    }

    private function authorizeExport(SuiviClassesFilterRequest $request): void
    {
        abort_unless($request->user()?->can('comptabilite.suivi_classes.export'), 403);
    }

    private function report(SuiviClassesFilterRequest $request, int $max): SituationClassesReport
    {
        $filters = $request->filters();
        $rapport = $this->situations->build($filters, pagination: false);
        abort_if($rapport['classes']->count() > $max, 422, 'Trop de classes : affinez les filtres avant export.');
        return new SituationClassesReport($rapport, $this->labels($filters));
    }

    private function labels(array $filters): array
    {
        return array_filter([
            'Année' => ESBTPAnneeUniversitaire::find($filters['annee'] ?? null)?->name ?? 'Non définie',
            'Filière' => ESBTPFiliere::find($filters['filiere'] ?? null)?->name,
            'Niveau' => ESBTPNiveauEtude::find($filters['niveau'] ?? null)?->name,
            'Classe' => ESBTPClasse::find($filters['classe'] ?? null)?->name,
            'Catégorie' => ESBTPFraisCategory::find($filters['categorie'] ?? null)?->nom,
            'Système' => $filters['systeme'] ?? null,
            'Recherche' => $filters['search'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
