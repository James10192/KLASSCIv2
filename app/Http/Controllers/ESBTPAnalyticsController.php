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
use App\Services\Analytics\AnalyticsScanCache;
use App\Services\Analytics\RecouvrementGapService;
use App\Services\EcheancierCoverageService;
use App\Services\EcheancierReadinessService;
use App\Services\ExportRenderer;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPRelance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ESBTPAnalyticsController extends Controller
{
    /**
     * En dessous de cette part d'inscriptions couvertes par un échéancier, les
     * prédictions reposent surtout sur le repli « une tranche » : l'écran mobile
     * l'annonce. Même seuil que la recommandation de `analytics:diagnose`.
     */
    private const COUVERTURE_MINIMALE_PCT = EcheancierCoverageService::SEUIL_FAIBLE_PCT;

    /** Statuts de relance comptés par étudiant à haut risque (liste mobile). */
    private const STATUTS_RELANCE_COMPTES = [ESBTPRelance::STATUT_ENVOYEE, ESBTPRelance::STATUT_INTENT];

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
        EcheancierCoverageService $echeancierCoverage,
    ): View {
        $context = AnalyticsContext::fromRequest($request);

        $cashFlowResult = $this->safePredict(new CachedPredictor($cashFlow), $context);
        $defaultRiskResult = $this->safePredict(new CachedPredictor($defaultRisk), $context);
        $anomalies = $this->safeDetect($anomalyDetector, $context);
        $cashFlowAccuracy = $this->safeAccuracy($accuracy, 'cash_flow');
        $recouvrementGaps = $this->safeRecouvrementGaps($recouvrementGap, $context);
        $lastComputedAt = $this->lastComputedAt();

        return view('esbtp.comptabilite.analytics.index', [
            // Écran mobile (shell m-*) : un seul état, dérivé des mêmes résultats.
            'mobileAnalytics'  => $this->mobileState(
                $cashFlowResult,
                $defaultRiskResult,
                $anomalies,
                $cashFlowAccuracy,
                $this->safeCoverage($echeancierCoverage, $context),
                $lastComputedAt,
                $echeancierReadiness,
            ),
            'cashFlow'         => $cashFlowResult,
            'defaultRisk'      => $defaultRiskResult,
            'anomalies'        => $anomalies,
            'cashFlowAccuracy' => $cashFlowAccuracy,
            'recouvrementGaps' => $recouvrementGaps,
            // Ces montants peuvent venir d'un balayage mémorisé : sans cette
            // date, le lecteur croirait lire du temps réel.
            'recouvrementGapsComputedAt' => $recouvrementGap->lastComputedAt(),
            'echeancierMode'   => $echeancierReadiness->mode(),
            'echeancierNote'   => $echeancierReadiness->noteForMode(),
            'context'          => $context,
            'annees'           => ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get(),
            'filieres'         => ESBTPFiliere::orderBy('name')->get(),
            'classes'          => ESBTPClasse::orderBy('name')->get(),
            'lastComputedAt'   => $lastComputedAt,
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
        AnalyticsScanCache $scanCache,
        AccuracyEvaluator $accuracy,
        EcheancierCoverageService $echeancierCoverage,
        EcheancierReadinessService $echeancierReadiness,
    ): JsonResponse {
        $context = AnalyticsContext::fromRequest($request);

        // Demander explicitement une actualisation doit rebalayer : sinon le
        // bouton renverrait les mêmes montants que ceux déjà à l'écran.
        $scanCache->invalidate();

        $cachedCashFlow = new CachedPredictor($cashFlow);
        $cachedRisk = new CachedPredictor($defaultRisk);
        $cachedCashFlow->forget($context);
        $cachedRisk->forget($context);

        $cashFlowResult = $this->safePredict($cachedCashFlow, $context);
        $defaultRiskResult = $this->safePredict($cachedRisk, $context);
        $anomalies = $this->safeDetect($anomalyDetector, $context);

        return response()->json([
            'success' => true,
            'cash_flow' => $cashFlowResult->toArray(),
            'default_risk' => $defaultRiskResult->toArray(),
            'anomalies' => array_map(fn ($a) => $a->toArray(), $anomalies),
            // Le tirer-pour-rafraîchir mobile relit cet état tel quel.
            'mobile' => $this->mobileState(
                $cashFlowResult,
                $defaultRiskResult,
                $anomalies,
                $this->safeAccuracy($accuracy, 'cash_flow'),
                $this->safeCoverage($echeancierCoverage, $context),
                $this->lastComputedAt(),
                $echeancierReadiness,
            ),
            'refreshed_at' => now()->toISOString(),
        ]);
    }

    /**
     * Déclenche le job daily + job anomalies. Retourne JSON pour AJAX
     * (no full page reload — voir rule laravel-ajax-blade-alpine.md).
     */
    public function runNow(Request $request, AnalyticsScanCache $scanCache): JsonResponse
    {
        try {
            // Même raison que dans refresh() : un recalcul demandé à la main ne
            // doit pas repartir des balayages mémorisés.
            $scanCache->invalidate();

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

        // La fraicheur voyage avec l export, comme a l ecran.
        //
        // L ecart de recouvrement peut venir de la memoire, et l ecran l annonce.
        // Un PDF ou un tableur, eux, se transmettent et se relisent des semaines plus
        // tard : sans cette ligne, un comptable lirait des chiffres memorises en les
        // croyant calcules a l instant. On passe par le bandeau des filtres appliques,
        // que les deux formats affichent deja.
        $calculeA = $recouvrementGap->lastComputedAt();

        if ($calculeA !== null) {
            $appliedFilters['Écart de recouvrement calculé le'] =
                $calculeA->locale('fr')->isoFormat('D MMMM YYYY [à] HH:mm');
        }

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
    public function updateSettings(Request $request): RedirectResponse|JsonResponse
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
            // Absents du formulaire mobile : facultatifs, jamais remis a zero.
            'fiabilite.stale_days'          => 'nullable|integer|min:7|max:365',
            'fiabilite.min_sample'          => 'nullable|integer|min:5|max:1000',
            'fiabilite.lookback_months'     => 'nullable|integer|min:3|max:24',
            'fiabilite.catchup_min_per_day' => 'nullable|integer|min:5|max:1000',
            'fiabilite.catchup_lag_days'    => 'nullable|integer|min:1|max:180',
            'fiabilite.catchup_alert_pct'   => 'nullable|numeric|min:5|max:100',
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

        foreach ($validated['fiabilite'] ?? [] as $cle => $valeur) {
            if ($valeur !== null) {
                $mappings['analytics.fiabilite.' . $cle] = $valeur;
            }
        }

        foreach ($mappings as $key => $value) {
            SettingsHelper::setOrCreate($key, (string) $value, 'analytics');
        }

        // Formulaire mobile : enregistrement sans rechargement (les erreurs de
        // validation partent déjà en 422 JSON par le validateur).
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Paramètres Analytics mis à jour.',
                'settings' => SettingsHelper::getAnalyticsSettings(),
            ]);
        }

        return redirect()
            ->route('esbtp.comptabilite.analytics.settings')
            ->with('success', 'Paramètres Analytics mis à jour.');
    }

    /**
     * Couverture des inscriptions actives par un échéancier (même lecture que
     * `analytics:diagnose`). Vide en cas d'erreur : l'écran n'affiche alors
     * pas de bandeau plutôt qu'un faux chiffre.
     *
     * @return array<string, mixed>
     */
    private function safeCoverage(EcheancierCoverageService $coverage, AnalyticsContext $context): array
    {
        try {
            $anneeId = $context->anneeId ?: ESBTPAnneeUniversitaire::where('is_current', true)->value('id');

            return $coverage->summary($anneeId ? (int) $anneeId : null);
        } catch (\Throwable $e) {
            Log::warning('Analytics coverage lookup failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * État de l'écran mobile (maquette S['comptable:analytics']) : les mêmes
     * résultats que le bureau, mais déjà lisibles — libellés de confiance,
     * pondération réelle du prédicteur, effectifs par tranche, liste « haut
     * risque » avec le nombre de relances, et les raisons de chaque chiffre.
     *
     * @param  array<int, \App\Domain\Analytics\DTOs\AnomalyAlert>  $anomalies
     * @param  array{score: ?float, label: ?string}  $cashFlowAccuracy
     * @param  array<string, mixed>  $coverage
     * @return array<string, mixed>
     */
    private function mobileState(
        PredictionResult $cashFlow,
        PredictionResult $defaultRisk,
        array $anomalies,
        array $cashFlowAccuracy,
        array $coverage,
        ?\Carbon\Carbon $lastComputedAt,
        EcheancierReadinessService $echeancierReadiness,
    ): array {
        $confiance = static fn (string $label): string => match ($label) {
            'tres_fiable' => 'Très fiable',
            'fiable' => 'Fiable',
            default => 'Indicatif',
        };
        $montant = static fn ($valeur): string => number_format((float) $valeur, 0, ',', ' ');

        // Pondération réellement appliquée par CashFlowPredictor::predict(), lue dans
        // metadata['weights'] ; repli sur l'ancienne déduction pour une prédiction
        // mise en cache avant l'ajout de cette clé. Rien n'est affiché sans prévision.
        $meta = $cashFlow->metadata;
        $ponderation = null;
        if ($cashFlow->isAvailable()) {
            $poids = $meta['weights'] ?? null;
            if (!is_array($poids)) {
                $echeancier = (float) ($meta['scheduled_revenue_next_month'] ?? 0);
                $historique = (int) ($meta['history_months'] ?? 0);
                $minHistorique = app(CashFlowPredictor::class)->minimumHistoryMonths();
                $poids = $echeancier > 0 && $historique >= $minHistorique
                    ? ['echeancier' => CashFlowPredictor::POIDS_ECHEANCIER, 'historique' => CashFlowPredictor::POIDS_HISTORIQUE]
                    : ['echeancier' => $echeancier > 0 ? 1.0 : 0.0, 'historique' => $echeancier > 0 ? 0.0 : 1.0];
            }
            $pctPoids = static fn ($v): string => (string) (int) round(((float) $v) * 100);
            $ponderation = match (true) {
                $poids['echeancier'] > 0 && $poids['historique'] > 0 => $pctPoids($poids['echeancier']) . ' % échéancier · ' . $pctPoids($poids['historique']) . ' % historique',
                $poids['echeancier'] > 0 => '100 % échéancier',
                default => '100 % historique',
            };
        }

        $moisCible = $cashFlow->targetDate
            ? \Carbon\Carbon::parse($cashFlow->targetDate)->locale('fr')->translatedFormat('F')
            : null;

        $riskMeta = $defaultRisk->metadata;
        $buckets = ['bas' => 0, 'moyen' => 0, 'haut' => 0];
        foreach (array_keys($buckets) as $k) {
            $buckets[$k] = (int) ($riskMeta['buckets'][$k] ?? 0);
        }
        $totalActifs = (int) ($riskMeta['total_actifs'] ?? 0);
        $pct = static fn (int $n) => $totalActifs > 0 ? round($n / $totalActifs * 100, 1) : 0.0;
        $hautPct = $pct($buckets['haut']);

        $top = collect($riskMeta['top_at_risk'] ?? [])
            ->filter(fn ($r) => ($r['level'] ?? null) === 'haut')
            ->values();
        $relances = $this->relancesParInscription($top->pluck('inscription_id')->all());

        $hautRisque = $top->map(function (array $r) use ($relances, $montant) {
            $nom = trim((string) ($r['etudiant_nom'] ?? ''));
            $mots = array_values(array_filter(preg_split('/\s+/u', $nom) ?: [], fn ($m) => $m !== ''));
            $initiales = $mots === [] ? '?' : mb_strtoupper(
                mb_substr($mots[0], 0, 1, 'UTF-8') . (count($mots) > 1 ? mb_substr($mots[count($mots) - 1], 0, 1, 'UTF-8') : ''),
                'UTF-8'
            );

            return [
                'inscription_id' => (int) $r['inscription_id'],
                'etudiant_id' => (int) ($r['etudiant_id'] ?? 0),
                'nom' => $nom !== '' ? $nom : '—',
                'initiales' => $initiales,
                'classe' => (string) ($r['classe_nom'] ?? ''),
                'score' => number_format((float) ($r['score'] ?? 0), 2, ',', ''),
                'jours_retard' => (int) ($r['jours_retard'] ?? 0),
                'relances' => (int) ($relances[(int) $r['inscription_id']] ?? 0),
                'ratio_paye_pct' => (int) round(((float) ($r['ratio_paye'] ?? 0)) * 100),
                'solde_restant' => (float) ($r['solde_restant'] ?? 0),
                'solde_restant_fmt' => $montant($r['solde_restant'] ?? 0),
                'montant_echu_fmt' => $montant($r['montant_echu'] ?? 0),
                'attendu_a_date_fmt' => $montant($r['attendu_a_date'] ?? 0),
                'paye_a_date_fmt' => $montant($r['paye_a_date'] ?? 0),
            ];
        })->values()->all();

        $anom = collect($anomalies);

        return [
            'devise' => 'FCFA',
            'cash_flow' => [
                'available' => $cashFlow->isAvailable(),
                'value' => $cashFlow->value,
                'value_fmt' => $cashFlow->isAvailable() ? $montant($cashFlow->value) : null,
                'compact' => $cashFlow->isAvailable() ? $this->montantCompact((float) $cashFlow->value) : null,
                'confidence' => $cashFlow->confidenceLabel,
                'confidence_label' => $confiance($cashFlow->confidenceLabel),
                'mois' => $moisCible,
                'ponderation' => $ponderation,
                'intervalle' => $cashFlow->confidenceInterval
                    ? ['lower_fmt' => $montant($cashFlow->confidenceInterval->lower), 'upper_fmt' => $montant($cashFlow->confidenceInterval->upper)]
                    : null,
                'precision_label' => match ($cashFlowAccuracy['label'] ?? null) {
                    'excellente' => 'Précision excellente',
                    'bonne' => 'Précision bonne',
                    null => null,
                    default => 'Précision à surveiller',
                },
                'explanation' => array_values($cashFlow->explanation),
            ],
            'risk' => [
                'available' => $defaultRisk->isAvailable(),
                'reason' => $defaultRisk->isAvailable() ? null : ($defaultRisk->explanation[0] ?? 'Analyse de risque indisponible.'),
                'total' => $totalActifs,
                'buckets' => $buckets,
                'pct' => ['bas' => $pct($buckets['bas']), 'moyen' => $pct($buckets['moyen']), 'haut' => $hautPct],
                'confidence' => $defaultRisk->confidenceLabel,
                'confidence_label' => $confiance($defaultRisk->confidenceLabel),
                'exposition_fmt' => $montant($riskMeta['total_solde_haut_risque'] ?? 0),
                // Saturation mesurée avant calibration (règle haut OU haut+moyen), repli ancien calcul.
                'is_saturated' => (bool) ($riskMeta['saturation_at_default']['is_saturated']
                    ?? ($totalActifs > 0 && $hautPct >= DefaultRiskPredictor::SATURATION_TRIGGER_PCT)),
                'auto_calibrated' => (bool) ($riskMeta['auto_calibrated'] ?? false),
                'echeancier_mode' => $riskMeta['echeancier_mode'] ?? null,
                'echeancier_note' => $riskMeta['echeancier_mode_note'] ?? null,
                'top' => $hautRisque,
                'explanation' => array_values($defaultRisk->explanation),
            ],
            'coverage' => [
                'pct' => isset($coverage['coverage_pct']) ? (float) $coverage['coverage_pct'] : null,
                'with' => (int) ($coverage['with_snapshot'] ?? 0),
                'total' => (int) ($coverage['total_actives'] ?? 0),
                'is_low' => isset($coverage['coverage_pct']) && (float) $coverage['coverage_pct'] < self::COUVERTURE_MINIMALE_PCT,
                'minimum_pct' => self::COUVERTURE_MINIMALE_PCT,
            ],
            'echeancier' => [
                'fallback' => $echeancierReadiness->mode() === EcheancierReadinessService::MODE_FALLBACK,
                'note' => $echeancierReadiness->noteForMode(),
            ],
            'anomalies' => [
                'critical' => $anom->where('severity', \App\Domain\Analytics\DTOs\AnomalyAlert::SEVERITY_CRITICAL)->count(),
                'warning' => $anom->where('severity', \App\Domain\Analytics\DTOs\AnomalyAlert::SEVERITY_WARNING)->count(),
            ],
            'last_computed' => $lastComputedAt?->locale('fr')->diffForHumans(),
            'never_computed' => $lastComputedAt === null,
        ];
    }

    /**
     * Relances déjà faites (envoyées ou intentions journalisées) par inscription.
     *
     * @param  array<int, int>  $inscriptionIds
     * @return array<int, int>
     */
    private function relancesParInscription(array $inscriptionIds): array
    {
        if ($inscriptionIds === []) {
            return [];
        }

        try {
            return ESBTPRelance::query()
                ->whereIn('inscription_id', $inscriptionIds)
                ->whereIn('statut', self::STATUTS_RELANCE_COMPTES)
                ->groupBy('inscription_id')
                ->selectRaw('inscription_id, COUNT(*) as total')
                ->pluck('total', 'inscription_id')
                ->map(fn ($n) => (int) $n)
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Analytics relances count failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /** « 18,4 M », « 850 k », « 0 » : lisible sur un bandeau de 96 px. */
    private function montantCompact(float $montant): string
    {
        if ($montant >= 1_000_000_000) {
            return number_format($montant / 1_000_000_000, 2, ',', ' ') . ' G';
        }
        if ($montant >= 1_000_000) {
            return number_format($montant / 1_000_000, 1, ',', ' ') . ' M';
        }
        if ($montant >= 10_000) {
            return number_format($montant / 1_000, 0, ',', ' ') . ' k';
        }

        return number_format($montant, 0, ',', ' ');
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
            'fiabilite' => \App\Domain\Analytics\Quality\PaymentQualityEvaluator::DEFAULTS,
            'recouvrement' => [
                'whatsapp_template' => "Bonjour {prenom}, votre solde de scolarité de {solde} FCFA est en retard de {retard} jours. Merci de régulariser dès que possible. — {ecole}",
            ],
        ];
    }
}
