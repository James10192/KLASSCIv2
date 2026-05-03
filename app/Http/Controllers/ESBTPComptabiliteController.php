<?php

namespace App\Http\Controllers;

use App\Actions\Comptabilite\BuildDashboardDataAction;
use App\DTOs\Comptabilite\ComptabiliteFilters;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Services\ComptabiliteService;
use App\Services\PerformanceMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ESBTPComptabiliteController extends Controller
{
    public function __construct(
        private readonly ComptabiliteService $comptabiliteService,
        private readonly PerformanceMonitoringService $performanceMonitor,
    ) {
        $this->middleware('auth');
        $this->middleware('comptabilite.access');
    }

    /**
     * Endpoint AJAX léger pour les KPIs temps réel (utilisé par les widgets dashboard).
     */
    public function kpisTempsReel(Request $request): JsonResponse
    {
        return $this->performanceMonitor->monitor('kpis_temps_reel', function () use ($request) {
            try {
                $kpis = $this->comptabiliteService->getKPIsDashboard($request->get('annee_id'));

                return response()->json([
                    'success' => true,
                    'kpis' => $kpis,
                    'cache_info' => [
                        'cached' => isset($kpis['cache_generated_at']),
                        'last_updated' => $kpis['last_updated'] ?? now()->toISOString(),
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('Erreur KPIs temps réel', [
                    'error' => $e->getMessage(),
                    'annee_id' => $request->get('annee_id'),
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Erreur lors de la récupération des KPIs',
                ], 500);
            }
        }, ['annee_id' => $request->get('annee_id')]);
    }

    /**
     * Page dashboard comptabilité (rendu HTML).
     */
    public function dashboard(Request $request, BuildDashboardDataAction $build)
    {
        $filters = ComptabiliteFilters::fromRequest($request);
        [$annee, $anneeActive, $referentiels] = $this->resolveReferentiels($filters);

        $data = $build($filters, $annee);

        return view('esbtp.comptabilite.dashboard', array_merge($data, [
            'annee' => $annee,
            'anneeActive' => $anneeActive,
            'annees' => $referentiels['annees'],
            'filieres' => $referentiels['filieres'],
            'classes' => $referentiels['classes'],
        ]));
    }

    /**
     * Endpoint AJAX retournant les mêmes données que dashboard() en JSON
     * (utilisé par les filtres dynamiques sans reload).
     */
    public function dashboardData(Request $request, BuildDashboardDataAction $build): JsonResponse
    {
        $filters = ComptabiliteFilters::fromRequest($request);
        [$annee] = $this->resolveReferentiels($filters);

        $data = $build($filters, $annee);

        return response()->json([
            'totalDue' => $data['totalDue'],
            'totalPaid' => $data['totalPaid'],
            'totalOverdue' => $data['totalOverdue'],
            'countPaid' => $data['countPaid'],
            'countPartiallyPaid' => $data['countPartiallyPaid'],
            'countOverdue' => $data['countOverdue'],
            'countDue' => $data['countDue'],
            'countToValidate' => $data['countToValidate'],
            'countOverdueTotal' => $data['countOverdueTotal'],
            'countValidatedToday' => $data['countValidatedToday'],
            'totalValidatedToday' => $data['totalValidatedToday'],
            'labelsMois' => $data['labelsMois'],
            'dataEncaissements' => $data['dataEncaissements'],
            'agingBuckets' => $data['agingBuckets'],
            'paiementsEnAttente' => BuildDashboardDataAction::pendingPaymentsToArray($data['paiementsEnAttente']),
            'anneeLabel' => $annee?->name ?? $annee?->libelle ?? '',
        ]);
    }

    /**
     * Résout l'année visualisée + les référentiels pour les filtres.
     *
     * @return array{0: ?ESBTPAnneeUniversitaire, 1: ?ESBTPAnneeUniversitaire, 2: array{annees: \Illuminate\Support\Collection, filieres: \Illuminate\Support\Collection, classes: \Illuminate\Support\Collection}}
     */
    private function resolveReferentiels(ComptabiliteFilters $filters): array
    {
        $annees = ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();
        $anneeActive = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $annee = $filters->anneeId
            ? $annees->firstWhere('id', $filters->anneeId)
            : ($anneeActive ?? $annees->first());

        return [
            $annee,
            $anneeActive,
            [
                'annees' => $annees,
                'filieres' => ESBTPFiliere::orderBy('name')->get(),
                'classes' => ESBTPClasse::orderBy('name')->get(),
            ],
        ];
    }
}
