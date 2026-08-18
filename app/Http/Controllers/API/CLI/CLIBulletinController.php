<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\ESBTP\BulletinBulkGenerationCliService;
use App\Services\ESBTP\BulletinRankRecalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CLIBulletinController extends BaseApiController
{
    public function __construct(
        private BulletinRankRecalculationService $rankRecalculationService,
        private BulletinBulkGenerationCliService $bulkGenerationService,
    ) {
        parent::__construct();
    }

    /**
     * POST /api/cli/bulletins/recalculate-ranks
     *   ?apply=1&annee_universitaire_id=&classe_id=&periode=semestre1,semestre2
     *
     * Recalcule les rangs persistes des bulletins BTS existants. Dry-run par
     * defaut. apply=1 ecrit bulletin.rang / effectif_classe, jamais le PDF.
     */
    public function recalculateRanks(Request $request): JsonResponse
    {
        $apply = $request->boolean('apply');
        $ability = $apply ? 'cli:write' : 'cli:read';
        if (! $request->user()->tokenCan($ability)) {
            return $this->errorResponse("Token missing {$ability} ability", [], 403);
        }

        $anneeId = $request->filled('annee_universitaire_id')
            ? (int) $request->input('annee_universitaire_id')
            : null;
        $classeId = $request->filled('classe_id') ? (int) $request->input('classe_id') : null;
        $periode = $request->filled('periode') ? (string) $request->input('periode') : null;

        try {
            $payload = $this->rankRecalculationService->recalculate(
                $apply,
                $anneeId,
                $classeId,
                $periode
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), [], 404);
        }

        return $this->successResponse(
            $payload,
            $apply ? 'Recalcul des rangs applique' : 'Recalcul des rangs (simulation)'
        );
    }

    /**
     * POST /api/cli/bulletins/generate-missing
     *   ?apply=1&classe_id=&annee_universitaire_id=&periode=semestre2
     *
     * Cree les bulletins BTS manquants d'une classe via le moteur de generation
     * existant. Dry-run par defaut. apply=1 ecrit, puis recalcule les rangs.
     */
    public function generateMissing(Request $request): JsonResponse
    {
        $apply = $request->boolean('apply');
        $ability = $apply ? 'cli:write' : 'cli:read';
        if (! $request->user()->tokenCan($ability)) {
            return $this->errorResponse("Token missing {$ability} ability", [], 403);
        }

        if (! $request->filled('classe_id')) {
            return $this->errorResponse('classe_id requis', [], 422);
        }

        $periode = $request->filled('periode') ? (string) $request->input('periode') : 'semestre2';
        $anneeId = $request->filled('annee_universitaire_id')
            ? (int) $request->input('annee_universitaire_id')
            : null;

        try {
            $payload = $this->bulkGenerationService->generate(
                $apply,
                $anneeId,
                (int) $request->input('classe_id'),
                $periode,
                $request->boolean('recalculer'),
                $request->input('incomplete_reason'),
                $request->user()
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), [], 404);
        }

        return $this->successResponse(
            $payload,
            $apply ? 'Generation officielle appliquee' : 'Generation officielle (simulation)'
        );
    }
}
