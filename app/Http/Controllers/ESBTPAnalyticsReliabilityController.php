<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\Quality\AnalyticsReliabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Bandeau « Fiabilité des données » et anciennete des impayes de la page
 * analytics, charges a part pour ne pas ralentir l'affichage de la page.
 */
class ESBTPAnalyticsReliabilityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('comptabilite.access');
        $this->middleware('can:comptabilite.analytics.view');
    }

    public function show(Request $request, AnalyticsReliabilityService $service): JsonResponse
    {
        $context = AnalyticsContext::fromRequest($request);

        try {
            return response()->json(['success' => true, 'data' => $service->build($context)]);
        } catch (\Throwable $e) {
            Log::error('[analytics-fiabilite] echec du calcul', ['context' => $context->toArray(), 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'La fiabilité des données n\'a pas pu être calculée.'], 500);
        }
    }
}
