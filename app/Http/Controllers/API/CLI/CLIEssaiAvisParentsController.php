<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\Controller;
use App\Services\Courriels\EssaiDesAvisAuxParents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Essai d'un avis aux parents, envoyé aux seules adresses de test de l'école.
 * Voir docs/api/CLI_ESSAI_AVIS_PARENTS.md.
 */
class CLIEssaiAvisParentsController extends Controller
{
    public function envoyer(Request $request, EssaiDesAvisAuxParents $essai): JsonResponse
    {
        if (! $request->user()?->tokenCan('cli:admin')) {
            return response()->json(['ok' => false, 'message' => 'Token missing cli:admin ability'], 403);
        }

        try {
            $payload = $request->validate([
                'avis' => ['required', 'string', 'max:60'],
                'dryRun' => ['sometimes', 'boolean'],
            ]);

            $resultat = $essai->envoyer($payload['avis'], (bool) ($payload['dryRun'] ?? true));
        } catch (ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Payload ou configuration invalide.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json($resultat, $resultat['ok'] ? 200 : 502);
    }
}
