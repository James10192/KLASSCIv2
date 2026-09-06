<?php

namespace App\Http\Controllers;

use App\Actions\Comptabilite\BuildDashboardDataAction;
use App\DTOs\Comptabilite\ComptabiliteFilters;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPPaiement;
use App\Services\ComptabiliteService;
use App\Services\PerformanceMonitoringService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ESBTPComptabiliteController extends Controller
{
    /** Même valeur que BuildDashboardDataAction::PAYMENT_STATUS_VALIDATED (constante privée là-bas). */
    private const PAYMENT_STATUS_VALIDATED = 'validé';

    /** Écran mobile : courbe des encaissements récents, un point par jour. */
    private const SERIE_RECENTE_JOURS = 30;

    /** Aligné sur le cache 60 s de BuildDashboardDataAction (même fraîcheur perçue). */
    private const SERIE_RECENTE_CACHE_SECONDES = 60;

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

        // FIX audit 2026-06-04 §2.12 : sans filtre user explicite, on scope sur
        // l'année résolue (couvre l'année courante par défaut). Garantit la cohérence
        // entre Dashboard UI et CLI (`api/cli/stats` / `payments-summary`).
        $effectiveFilters = $annee ? $filters->withAnneeDefault((int) $annee->id) : $filters;

        $data = $build($effectiveFilters, $annee);

        return view('esbtp.comptabilite.dashboard', array_merge($data, [
            'annee' => $annee,
            'anneeActive' => $anneeActive,
            'annees' => $referentiels['annees'],
            'filieres' => $referentiels['filieres'],
            'classes' => $referentiels['classes'],
            'serieRecente' => $this->serieEncaissementsRecents($effectiveFilters),
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

        // Idem dashboard() : filtre annee_courante par défaut pour cohérence CLI ↔ UI.
        $effectiveFilters = $annee ? $filters->withAnneeDefault((int) $annee->id) : $filters;

        $data = $build($effectiveFilters, $annee);

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
            'serieRecente' => $this->serieEncaissementsRecents($effectiveFilters),
        ]);
    }

    /**
     * Encaissements validés des derniers jours, un point par jour (écran mobile :
     * courbe SVG légère à la place de Chart.js). Mêmes filtres que le reste du
     * tableau de bord ; cache court aligné sur BuildDashboardDataAction.
     *
     * Un jour sans encaissement vaut 0 : c'est une valeur, la courbe la trace.
     *
     * @return array{labels: array<int, string>, data: array<int, float>, total: float, jours: int}
     */
    private function serieEncaissementsRecents(ComptabiliteFilters $filters): array
    {
        $cle = sprintf(
            'dashboard_compta_serie%dj_%s_%s_%s',
            self::SERIE_RECENTE_JOURS,
            $filters->anneeId ?? 'all',
            $filters->filiereId ?? 'all',
            $filters->classeId ?? 'all',
        );

        return Cache::remember($cle, self::SERIE_RECENTE_CACHE_SECONDES, function () use ($filters) {
            $fin = Carbon::today();
            $debut = $fin->copy()->subDays(self::SERIE_RECENTE_JOURS - 1);

            $parJour = ESBTPPaiement::query()
                ->whereNull('deleted_at')
                ->where('status', self::PAYMENT_STATUS_VALIDATED)
                ->whereDate('date_paiement', '>=', $debut)
                ->whereDate('date_paiement', '<=', $fin)
                ->when($filters->anneeId, fn ($q) => $q->whereHas('inscription', fn ($q2) => $q2->where('annee_universitaire_id', $filters->anneeId)))
                ->when($filters->filiereId, fn ($q) => $q->whereHas('inscription.classe', fn ($q2) => $q2->where('filiere_id', $filters->filiereId)))
                ->when($filters->classeId, fn ($q) => $q->whereHas('inscription', fn ($q2) => $q2->where('classe_id', $filters->classeId)))
                ->selectRaw('DATE(date_paiement) as jour, SUM('.ESBTPPaiement::sqlCashCase().') as total')
                ->groupBy('jour')
                ->pluck('total', 'jour');

            $labels = [];
            $data = [];
            for ($jour = $debut->copy(); $jour->lte($fin); $jour->addDay()) {
                $labels[] = $jour->translatedFormat('j M');
                $data[] = (float) ($parJour[$jour->toDateString()] ?? 0);
            }

            return [
                'labels' => $labels,
                'data' => $data,
                'total' => (float) array_sum($data),
                'jours' => self::SERIE_RECENTE_JOURS,
            ];
        });
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
