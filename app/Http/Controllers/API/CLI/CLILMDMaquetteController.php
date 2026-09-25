<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\Controller;
use App\Services\LMD\CreditDeMaquette;
use App\Services\LMD\LectureDeMaquetteLmd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/cli/lmd/maquette — la maquette LMD telle que chaque parcours la voit.
 *
 * Lecture seule. Sert a diagnostiquer a distance « je vois dans ce parcours un
 * element reserve a l'autre », sans session web sur l'instance.
 *
 * @see LectureDeMaquetteLmd
 * @see docs/api/CLI_LMD_MAQUETTE.md
 */
class CLILMDMaquetteController extends Controller
{
    public function lire(Request $request, LectureDeMaquetteLmd $lecture): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return response()->json(['success' => false, 'message' => 'Token missing cli:read ability'], 403);
        }

        $valide = $request->validate([
            'parcours_id' => 'nullable|integer|exists:esbtp_lmd_parcours,id',
            'code_ue' => 'nullable|string|max:50',
        ]);

        return response()->json([
            'success' => true,
            'data' => $lecture->lire(
                isset($valide['parcours_id']) ? (int) $valide['parcours_id'] : null,
                $valide['code_ue'] ?? null
            ),
        ]);
    }

    /**
     * POST /api/cli/lmd/planifications/reparer-credits — recense, puis repare
     * sur demande, les planifications LMD laissees a 0 credit par la saisie
     * d'heures. Simulation par defaut.
     *
     * @see docs/api/CLI_LMD_MAQUETTE.md
     */
    public function reparerCredits(Request $request, CreditDeMaquette $credits): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return response()->json(['success' => false, 'message' => 'Token missing cli:admin ability'], 403);
        }

        $valide = $request->validate([
            'ids' => 'sometimes|array',
            'ids.*' => 'integer',
        ]);
        $simulation = $request->boolean('dry_run', true);

        try {
            $resultat = $credits->reparer($simulation, $valide['ids'] ?? []);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json([
            'success' => true,
            'dry_run' => $simulation,
            'data' => $resultat,
        ]);
    }
}
