<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\Usage\UsageReportService;
use App\Services\Usage\UsageWindow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CLIUsageReportController extends BaseApiController
{
    /**
     * GET /api/cli/usage/report?from=AAAA-MM-JJ&to=AAAA-MM-JJ[&exclure=1,2][&nominatif=1]
     *
     * Lecture seule. Les noms du personnel ne sortent qu'avec nominatif=1 et
     * un jeton cli:admin ; sinon chaque compte est designe par son numero.
     */
    public function report(Request $request, UsageReportService $service): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $maxDays = (int) config('usage_report.max_window_days', 186);
        $validator = Validator::make($request->query(), [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'exclure' => ['nullable', 'regex:/^\d+(,\d+)*$/'],
            'nominatif' => ['nullable', 'in:0,1'],
        ]);
        if ($validator->fails()) {
            return $this->errorResponse('Paramètres invalides', $validator->errors()->toArray(), 422);
        }

        $window = UsageWindow::fromDates($request->query('from'), $request->query('to'));
        if ($window->days() > $maxDays) {
            return $this->errorResponse("La période est limitée à {$maxDays} jours.", [], 422);
        }

        $withNames = $request->query('nominatif') === '1';
        if ($withNames && !$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Le rapport nominatif exige un jeton cli:admin', [], 403);
        }

        $excluded = array_map('intval', array_filter(explode(',', (string) $request->query('exclure', ''))));

        try {
            return $this->successResponse($service->build($window, $excluded, $withNames));
        } catch (\Throwable $e) {
            Log::error('[usage-report] echec du calcul', ['from' => $window->from->toDateString(), 'to' => $window->to->toDateString(), 'error' => $e->getMessage()]);

            return $this->errorResponse('Le rapport d\'usage n\'a pas pu être calculé.', [], 500);
        }
    }
}
