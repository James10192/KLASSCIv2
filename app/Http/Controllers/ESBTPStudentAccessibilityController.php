<?php

namespace App\Http\Controllers;

use App\Domain\Exports\Reports\AccessibilityReport;
use App\Domain\Students\Accessibility\Actions\AttachAccessibilityProfile;
use App\Http\Requests\StoreAccessibilityProfileRequest;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPStudentAccessibilityProfile;
use App\Services\ExportRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cohort + per-student endpoints pour le suivi des étudiants en situation
 * de handicap. Permissions :
 *   - students.accessibility.view      → résumé + cohorte
 *   - students.accessibility.view_full → description médicale complète
 *   - students.accessibility.edit      → upsert profil
 *   - students.accessibility.export    → PDF/Excel
 *   - students.accessibility.view_own  → étudiant voit son propre profil
 */
class ESBTPStudentAccessibilityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $this->authorize('students.accessibility.view');

        [$rows, $kpis, $filters] = $this->buildCohort($request);

        $classes = ESBTPClasse::orderBy('name')->get(['id', 'name']);
        $filieres = ESBTPFiliere::orderBy('name')->get(['id', 'name']);
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get(['id', 'name']);

        return view('esbtp.accessibility.index', [
            'rows'           => $rows,
            'kpis'           => $kpis,
            'appliedFilters' => $filters,
            'classes'        => $classes,
            'filieres'       => $filieres,
            'niveaux'        => $niveaux,
            'categories'     => ESBTPStudentAccessibilityProfile::CATEGORIES,
            'accommodations' => ESBTPStudentAccessibilityProfile::ACCOMMODATIONS,
        ]);
    }

    public function show(ESBTPEtudiant $etudiant): JsonResponse
    {
        $this->authorize('students.accessibility.view');

        $profile = $etudiant->accessibilityProfile;

        if (! $profile) {
            return response()->json(['exists' => false]);
        }

        $canViewFull = Auth::user()->can('students.accessibility.view_full');

        return response()->json([
            'exists' => true,
            'profile' => [
                'id' => $profile->id,
                'has_official_recognition' => $profile->has_official_recognition,
                'recognition_reference'    => $profile->recognition_reference,
                'categories'               => $profile->categories ?? [],
                'category_labels'          => $profile->categoryLabels(),
                'short_description'        => $profile->short_description,
                'full_description'         => $canViewFull ? $profile->full_description : null,
                'accommodations'           => $profile->accommodations ?? [],
                'accommodation_labels'     => $profile->accommodationLabels(),
                'accommodations_notes'     => $canViewFull ? $profile->accommodations_notes : null,
                'requires_third_time'      => $profile->requires_third_time,
                'third_time_percentage'    => $profile->third_time_percentage,
                'assistant_required'       => $profile->assistant_required,
                'effective_from'           => $profile->effective_from?->toDateString(),
                'effective_to'             => $profile->effective_to?->toDateString(),
                'currently_effective'      => $profile->isCurrentlyEffective(),
                'updated_at'               => $profile->updated_at?->toDateTimeString(),
                'updated_by'               => $profile->updatedBy?->name,
            ],
        ]);
    }

    public function store(StoreAccessibilityProfileRequest $request, ESBTPEtudiant $etudiant, AttachAccessibilityProfile $action): RedirectResponse
    {
        $existed = (bool) $etudiant->accessibilityProfile;
        $action->execute($etudiant->id, $request->validated(), Auth::id());

        Log::info('Accessibility profile ' . ($existed ? 'updated' : 'created'), [
            'etudiant_id' => $etudiant->id,
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->back()
            ->with('success', $existed
                ? 'Profil d\'accessibilité mis à jour.'
                : 'Profil d\'accessibilité enregistré.');
    }

    public function destroy(ESBTPEtudiant $etudiant): RedirectResponse
    {
        $this->authorize('students.accessibility.edit');

        $profile = $etudiant->accessibilityProfile;
        if ($profile) {
            $profile->delete();
            Log::info('Accessibility profile soft-deleted', [
                'etudiant_id' => $etudiant->id,
                'user_id' => Auth::id(),
            ]);
        }

        return redirect()->back()->with('success', 'Profil d\'accessibilité supprimé.');
    }

    public function previewPdf(Request $request, ExportRenderer $renderer): Response
    {
        $this->authorize('students.accessibility.export');

        return $renderer->pdfPreview($this->buildReport($request));
    }

    public function exportPdf(Request $request, ExportRenderer $renderer): Response
    {
        $this->authorize('students.accessibility.export');

        return $renderer->pdfDownload($this->buildReport($request));
    }

    public function exportExcel(Request $request, ExportRenderer $renderer)
    {
        $this->authorize('students.accessibility.export');

        return $renderer->excelDownload($this->buildReport($request));
    }

    private function buildReport(Request $request): AccessibilityReport
    {
        [$rows, $kpis, $filters] = $this->buildCohort($request);

        return new AccessibilityReport(
            rows: $rows,
            appliedFilters: $filters,
            kpis: $kpis,
            includeFullDescription: Auth::user()?->can('students.accessibility.view_full') ?? false,
        );
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: array, 2: array<string,string>}
     */
    private function buildCohort(Request $request): array
    {
        $query = ESBTPStudentAccessibilityProfile::query()
            ->with(['etudiant.inscription.classe', 'etudiant.inscription.filiere', 'etudiant.inscription.niveau']);

        if ($category = $request->query('category')) {
            $query->withCategory($category);
        }

        if ($accommodation = $request->query('accommodation')) {
            $query->withAccommodation($accommodation);
        }

        if ($request->boolean('third_time_only')) {
            $query->where('requires_third_time', true);
        }

        if ($request->boolean('assistant_only')) {
            $query->where('assistant_required', true);
        }

        if ($request->boolean('recognition_only')) {
            $query->where('has_official_recognition', true);
        }

        $profiles = $query->get();

        $classeId = $request->query('classe');
        $filiereId = $request->query('filiere');
        $niveauId = $request->query('niveau');

        $rows = $profiles->map(function (ESBTPStudentAccessibilityProfile $profile) {
            $etudiant = $profile->etudiant;
            $inscription = $etudiant?->inscription;

            return [
                'profile'     => $profile,
                'etudiant'    => $etudiant,
                'inscription' => $inscription,
            ];
        })->filter(fn ($r) => $r['etudiant'] !== null);

        if ($classeId) {
            $rows = $rows->filter(fn ($r) => optional($r['inscription']?->classe)->id == $classeId);
        }
        if ($filiereId) {
            $rows = $rows->filter(fn ($r) => optional($r['inscription']?->filiere)->id == $filiereId);
        }
        if ($niveauId) {
            $rows = $rows->filter(fn ($r) => optional($r['inscription']?->niveau)->id == $niveauId);
        }

        $rows = $rows->values();

        $kpis = [
            'total'            => $rows->count(),
            'tiers_temps'      => $rows->filter(fn ($r) => $r['profile']->requires_third_time)->count(),
            'assistant'        => $rows->filter(fn ($r) => $r['profile']->assistant_required)->count(),
            'recognition'      => $rows->filter(fn ($r) => $r['profile']->has_official_recognition)->count(),
        ];

        $filters = array_filter([
            'Catégorie'      => $category ? (ESBTPStudentAccessibilityProfile::CATEGORIES[$category] ?? $category) : null,
            'Aménagement'    => $accommodation ? (ESBTPStudentAccessibilityProfile::ACCOMMODATIONS[$accommodation] ?? $accommodation) : null,
            'Tiers-temps'    => $request->boolean('third_time_only') ? 'Oui' : null,
            'Assistant'      => $request->boolean('assistant_only') ? 'Oui' : null,
            'Reconnaissance' => $request->boolean('recognition_only') ? 'Officielle' : null,
            'Classe'         => $classeId ? optional(ESBTPClasse::find($classeId))->name : null,
            'Filière'        => $filiereId ? optional(ESBTPFiliere::find($filiereId))->name : null,
            'Niveau'         => $niveauId ? optional(ESBTPNiveauEtude::find($niveauId))->name : null,
        ]);

        return [$rows, $kpis, $filters];
    }
}
