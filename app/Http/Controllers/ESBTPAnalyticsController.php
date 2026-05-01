<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\PredictionResult;
use App\Domain\Analytics\Predictors\CashFlowPredictor;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ESBTPAnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('comptabilite.access');
        $this->middleware('can:comptabilite.analytics.view');
    }

    /**
     * Page Analytics premium — prédictions financières.
     */
    public function index(Request $request, CashFlowPredictor $cashFlow)
    {
        $context = AnalyticsContext::fromRequest($request);
        $cashFlowPrediction = $this->safePredict($cashFlow, $context);

        return view('esbtp.comptabilite.analytics.index', [
            'cashFlow' => $cashFlowPrediction,
            'context' => $context,
            'annees' => ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get(),
            'filieres' => ESBTPFiliere::orderBy('name')->get(),
            'classes' => ESBTPClasse::orderBy('name')->get(),
        ]);
    }

    /**
     * Endpoint AJAX JSON pour rafraîchir sans reload.
     */
    public function refresh(Request $request, CashFlowPredictor $cashFlow): JsonResponse
    {
        $context = AnalyticsContext::fromRequest($request);
        $prediction = $this->safePredict($cashFlow, $context);

        return response()->json([
            'success' => $prediction->value !== null,
            'cash_flow' => $prediction->toArray(),
            'refreshed_at' => now()->toISOString(),
        ]);
    }

    private function safePredict(CashFlowPredictor $predictor, AnalyticsContext $context): PredictionResult
    {
        try {
            return $predictor->predict($context);
        } catch (\Throwable $e) {
            Log::error('Analytics CashFlow prediction failed', [
                'context' => $context->toArray(),
                'error' => $e->getMessage(),
            ]);
            return PredictionResult::unavailable(
                $predictor->name(),
                'Erreur technique — l\'équipe a été notifiée',
            );
        }
    }
}
