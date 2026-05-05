<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\AccuracyEvaluator;
use App\Domain\Analytics\Cache\CachedPredictor;
use App\Domain\Analytics\Detectors\AnomalyDetector;
use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\PredictionResult;
use App\Domain\Analytics\Predictors\CashFlowPredictor;
use App\Domain\Analytics\Predictors\DefaultRiskPredictor;
use App\Domain\Analytics\Predictors\PredictorInterface;
use App\Domain\Exports\Reports\AnalyticsReport;
use App\Helpers\SettingsHelper;
use App\Jobs\ComputeAnalyticsPredictionsJob;
use App\Jobs\DetectAnalyticsAnomaliesJob;
use App\Services\Analytics\RecouvrementGapService;
use App\Services\EcheancierReadinessService;
use App\Services\ExportRenderer;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ESBTPAnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('comptabilite.access');
        $this->middleware('can:comptabilite.analytics.view')->only(['index', 'refresh']);
    }

    /**
     * Page Analytics premium — cash flow + default risk + anomalies en serveur-rendered.
     */
    public function index(
        Request $request,
        CashFlowPredictor $cashFlow,
        DefaultRiskPredictor $defaultRisk,
        AnomalyDetector $anomalyDetector,
        AccuracyEvaluator $accuracy,
        RecouvrementGapService $recouvrementGap,
        EcheancierReadinessService $echeancierReadiness,
    ): View {
        $context = AnalyticsContext::fromRequest($request);

        $cashFlowResult = $this->safePredict(new CachedPredictor($cashFlow), $context);
        $defaultRiskResult = $this->safePredict(new CachedPredictor($defaultRisk), $context);
        $anomalies = $this->safeDetect($anomalyDetector, $context);
        $cashFlowAccuracy = $this->safeAccuracy($accuracy, 'cash_flow');
        $recouvrementGaps = $this->safeRecouvrementGaps($recouvrementGap, $context);

        return view('esbtp.comptabilite.analytics.index', [
            'cashFlow'         => $cashFlowResult,
            'defaultRisk'      => $defaultRiskResult,
            'anomalies'        => $anomalies,
            'cashFlowAccuracy' => $cashFlowAccuracy,
            'recouvrementGaps' => $recouvrementGaps,
            'echeancierMode'   => $echeancierReadiness->mode(),
            'echeancierNote'   => $echeancierReadiness->noteForMode(),
            'context'          => $context,
            'annees'           => ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get(),
            'filieres'         => ESBTPFiliere::orderBy('name')->get(),
            'classes'          => ESBTPClasse::orderBy('name')->get(),
            'lastComputedAt'   => $this->lastComputedAt(),
        ]);
    }

    /**
     * @return array<string, array{expected: float, paid: float, gap: float, gap_ratio: float}>
     */
    private function safeRecouvrementGaps(RecouvrementGapService $service, AnalyticsContext $context): array
    {
        try {
            return $service->monthlyGaps($context, 6);
        } catch (\Throwable $e) {
            Log::warning('Analytics recouvrement gap fetch failed', [
                'context' => $context->toArray(),
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Endpoint AJAX JSON — recompute synchrone des 3 sections.
     */
    public function refresh(
        Request $request,
        CashFlowPredictor $cashFlow,
        DefaultRiskPredictor $defaultRisk,
        AnomalyDetector $anomalyDetector,
    ): JsonResponse {
        $context = AnalyticsContext::fromRequest($request);

        $cachedCashFlow = new CachedPredictor($cashFlow);
        $cachedRisk = new CachedPredictor($defaultRisk);
        $cachedCashFlow->forget($context);
        $cachedRisk->forget($context);

        return response()->json([
            'success' => true,
            'cash_flow' => $this->safePredict($cachedCashFlow, $context)->toArray(),
            'default_risk' => $this->safePredict($cachedRisk, $context)->toArray(),
            'anomalies' => array_map(fn ($a) => $a->toArray(), $this->safeDetect($anomalyDetector, $context)),
            'refreshed_at' => now()->toISOString(),
        ]);
    }

    /**
     * Déclenche le job daily + job anomalies. Retourne JSON pour AJAX
     * (no full page reload — voir rule laravel-ajax-blade-alpine.md).
     */
    public function runNow(Request $request): JsonResponse
    {
        try {
            ComputeAnalyticsPredictionsJob::dispatch();
            DetectAnalyticsAnomaliesJob::dispatch();

            return response()->json([
                'success' => true,
                'message' => 'Recalcul lancé en arrière-plan. Les prédictions seront mises à jour sous peu.',
                'dispatched_at' => now()->toISOString(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Analytics runNow dispatch failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Impossible de lancer le recalcul : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview PDF Analytics inline (nouvelle tab).
     */
    public function previewPdf(
        Request $request,
        CashFlowPredictor $cashFlow,
        DefaultRiskPredictor $defaultRisk,
        AnomalyDetector $anomalyDetector,
        ExportRenderer $renderer,
        RecouvrementGapService $recouvrementGap,
        EcheancierReadinessService $echeancierReadiness,
    ) {
        return $renderer->pdfPreview($this->buildAnalyticsReport($request, $cashFlow, $defaultRisk, $anomalyDetector, $recouvrementGap, $echeancierReadiness));
    }

    /**
     * Download PDF Analytics.
     */
    public function exportPdf(
        Request $request,
        CashFlowPredictor $cashFlow,
        DefaultRiskPredictor $defaultRisk,
        AnomalyDetector $anomalyDetector,
        ExportRenderer $renderer,
        RecouvrementGapService $recouvrementGap,
        EcheancierReadinessService $echeancierReadiness,
    ) {
        return $renderer->pdfDownload($this->buildAnalyticsReport($request, $cashFlow, $defaultRisk, $anomalyDetector, $recouvrementGap, $echeancierReadiness));
    }

    /**
     * Download Excel Analytics multi-sheets.
     */
    public function exportExcel(
        Request $request,
        CashFlowPredictor $cashFlow,
        DefaultRiskPredictor $defaultRisk,
        AnomalyDetector $anomalyDetector,
        ExportRenderer $renderer,
        RecouvrementGapService $recouvrementGap,
        EcheancierReadinessService $echeancierReadiness,
    ) {
        return $renderer->excelDownload($this->buildAnalyticsReport($request, $cashFlow, $defaultRisk, $anomalyDetector, $recouvrementGap, $echeancierReadiness));
    }

    private function buildAnalyticsReport(
        Request $request,
        CashFlowPredictor $cashFlow,
        DefaultRiskPredictor $defaultRisk,
        AnomalyDetector $anomalyDetector,
        RecouvrementGapService $recouvrementGap,
        EcheancierReadinessService $echeancierReadiness,
    ): AnalyticsReport {
        $context = AnalyticsContext::fromRequest($request);
        $cashFlowResult = $this->safePredict(new CachedPredictor($cashFlow), $context);
        $defaultRiskResult = $this->safePredict(new CachedPredictor($defaultRisk), $context);
        $anomalies = $this->safeDetect($anomalyDetector, $context);
        $recouvrementGaps = $this->safeRecouvrementGaps($recouvrementGap, $context);

        $appliedFilters = array_filter([
            'Année' => $context->anneeId ? optional(ESBTPAnneeUniversitaire::find($context->anneeId))->name : null,
            'Filière' => $context->filiereId ? optional(ESBTPFiliere::find($context->filiereId))->name : null,
            'Classe' => $context->classeId ? optional(ESBTPClasse::find($context->classeId))->name : null,
        ]);

        return new AnalyticsReport(
            $cashFlowResult,
            $defaultRiskResult,
            $anomalies,
            $appliedFilters,
            $recouvrementGaps,
            $echeancierReadiness->mode(),
            $echeancierReadiness->noteForMode(),
        );
    }

    /**
     * Page settings — paramètres du moteur Analytics (poids/seuils).
     */
    public function settings(): View
    {
        return view('esbtp.comptabilite.analytics.settings', [
            'settings' => SettingsHelper::getAnalyticsSettings(),
            'defaults' => $this->defaultSettings(),
        ]);
    }

    /**
     * POST settings — persiste les paramètres validés.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_risk.weight_solde'      => 'required|numeric|min:0|max:10',
            'default_risk.weight_retard'     => 'required|numeric|min:0|max:10',
            'default_risk.weight_engagement' => 'required|numeric|min:0|max:10',
            'default_risk.weight_montant'    => 'required|numeric|min:0|max:10',
            'default_risk.bias'              => 'required|numeric|min:-10|max:10',
            'default_risk.threshold_high'    => 'required|numeric|min:0.5|max:0.95',
            'default_risk.threshold_medium'  => 'required|numeric|min:0.05|max:0.5',
            'default_risk.top_n'             => 'required|integer|min:10|max:500',
            'anomaly.z_warning'              => 'required|numeric|min:1|max:5',
            'anomaly.z_critical'             => 'required|numeric|min:1.5|max:6',
            'anomaly.payment_outlier_multiplier' => 'required|numeric|min:1.5|max:10',
            'anomaly.recouvrement_gap_warning_pct'  => 'required|numeric|min:5|max:80',
            'anomaly.recouvrement_gap_critical_pct' => 'required|numeric|min:10|max:95',
            'anomaly.recouvrement_gap_min_expected' => 'required|numeric|min:0|max:100000000',
            'anomaly.notifications_enabled'  => 'nullable|in:0,1',
            'recouvrement.whatsapp_template' => 'nullable|string|max:1000',
        ]);

        $mappings = [
            'analytics.default_risk.weight.solde'           => $validated['default_risk']['weight_solde'],
            'analytics.default_risk.weight.retard'          => $validated['default_risk']['weight_retard'],
            'analytics.default_risk.weight.engagement'      => $validated['default_risk']['weight_engagement'],
            'analytics.default_risk.weight.montant'         => $validated['default_risk']['weight_montant'],
            'analytics.default_risk.bias'                   => $validated['default_risk']['bias'],
            'analytics.default_risk.threshold_high'         => $validated['default_risk']['threshold_high'],
            'analytics.default_risk.threshold_medium'       => $validated['default_risk']['threshold_medium'],
            'analytics.default_risk.top_n'                  => $validated['default_risk']['top_n'],
            'analytics.anomaly.z_warning'                   => $validated['anomaly']['z_warning'],
            'analytics.anomaly.z_critical'                  => $validated['anomaly']['z_critical'],
            'analytics.anomaly.payment_outlier_multiplier'  => $validated['anomaly']['payment_outlier_multiplier'],
            'analytics.anomaly.recouvrement_gap_warning_pct'  => $validated['anomaly']['recouvrement_gap_warning_pct'],
            'analytics.anomaly.recouvrement_gap_critical_pct' => $validated['anomaly']['recouvrement_gap_critical_pct'],
            'analytics.anomaly.recouvrement_gap_min_expected' => $validated['anomaly']['recouvrement_gap_min_expected'],
            'analytics.anomaly.notifications_enabled'       => $validated['anomaly']['notifications_enabled'] ?? '0',
            'analytics.recouvrement.whatsapp_template'      => $validated['recouvrement']['whatsapp_template'] ?? '',
        ];

        foreach ($mappings as $key => $value) {
            SettingsHelper::setOrCreate($key, (string) $value, 'analytics');
        }

        return redirect()
            ->route('esbtp.comptabilite.analytics.settings')
            ->with('success', 'Paramètres Analytics mis à jour.');
    }

    /**
     * @return array{score: ?float, label: ?string}
     */
    private function safeAccuracy(AccuracyEvaluator $evaluator, string $predictor): array
    {
        try {
            $score = $evaluator->averageAccuracy($predictor);
            return [
                'score' => $score,
                'label' => $score === null ? null : AccuracyEvaluator::labelForScore($score),
            ];
        } catch (\Throwable $e) {
            Log::warning('Analytics accuracy lookup failed', [
                'predictor' => $predictor,
                'error' => $e->getMessage(),
            ]);
            return ['score' => null, 'label' => null];
        }
    }

    private function safePredict(PredictorInterface $predictor, AnalyticsContext $context): PredictionResult
    {
        try {
            return $predictor->predict($context);
        } catch (\Throwable $e) {
            Log::error('Analytics prediction failed', [
                'predictor' => $predictor->name(),
                'context' => $context->toArray(),
                'error' => $e->getMessage(),
            ]);
            if ($predictor->name() === 'default_risk') {
                return PredictionResult::unavailable(
                    $predictor->name(),
                    "Impossible de calculer le risque de defaut pour le moment. Verifiez que les migrations et les regles d'echeance sont bien deployees.",
                );
            }

            return PredictionResult::unavailable(
                $predictor->name(),
                'Erreur technique — l\'équipe a été notifiée',
            );
        }
    }

    /**
     * @return array<int, \App\Domain\Analytics\DTOs\AnomalyAlert>
     */
    private function safeDetect(AnomalyDetector $detector, AnalyticsContext $context): array
    {
        try {
            return $detector->detect($context);
        } catch (\Throwable $e) {
            Log::error('Analytics anomaly detection failed', [
                'context' => $context->toArray(),
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function lastComputedAt(): ?\Carbon\Carbon
    {
        try {
            return \App\Models\AnalyticsPrediction::query()
                ->orderByDesc('computed_at')
                ->first()?->computed_at;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function defaultSettings(): array
    {
        return [
            'default_risk' => [
                'weight_solde'      => DefaultRiskPredictor::DEFAULT_WEIGHT_SOLDE,
                'weight_retard'     => DefaultRiskPredictor::DEFAULT_WEIGHT_RETARD,
                'weight_engagement' => DefaultRiskPredictor::DEFAULT_WEIGHT_ENGAGEMENT,
                'weight_montant'    => DefaultRiskPredictor::DEFAULT_WEIGHT_MONTANT,
                'bias'              => DefaultRiskPredictor::DEFAULT_BIAS,
                'threshold_high'    => DefaultRiskPredictor::DEFAULT_THRESHOLD_HIGH,
                'threshold_medium'  => DefaultRiskPredictor::DEFAULT_THRESHOLD_MEDIUM,
                'top_n'             => DefaultRiskPredictor::DEFAULT_TOP_N,
            ],
            'anomaly' => [
                'z_warning'                  => AnomalyDetector::DEFAULT_Z_WARNING,
                'z_critical'                 => AnomalyDetector::DEFAULT_Z_CRITICAL,
                'payment_outlier_multiplier' => AnomalyDetector::DEFAULT_PAYMENT_OUTLIER_MULTIPLIER,
                'recouvrement_gap_warning_pct'  => AnomalyDetector::DEFAULT_RECOUVREMENT_GAP_WARNING_PCT,
                'recouvrement_gap_critical_pct' => AnomalyDetector::DEFAULT_RECOUVREMENT_GAP_CRITICAL_PCT,
                'recouvrement_gap_min_expected' => AnomalyDetector::DEFAULT_RECOUVREMENT_GAP_MIN_EXPECTED,
            ],
            'recouvrement' => [
                'whatsapp_template' => "Bonjour {prenom}, votre solde de scolarité de {solde} FCFA est en retard de {retard} jours. Merci de régulariser dès que possible. — {ecole}",
            ],
        ];
    }
}
