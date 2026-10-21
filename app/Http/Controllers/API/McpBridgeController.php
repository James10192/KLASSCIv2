<?php

namespace App\Http\Controllers\API;

use App\Domain\Assistant\Mcp\CatalogueLectureExterne;
use App\Domain\Assistant\Mcp\RequetePontSignee;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Appelé exclusivement par la passerelle Nanan Connect après vérification OAuth.
 * Aucun jeton CLI administrateur, aucun utilisateur/tenant fourni par un LLM.
 */
final class McpBridgeController extends Controller
{
    public function __invoke(
        Request $request,
        RequetePontSignee $signature,
        CatalogueLectureExterne $lectures
    ): JsonResponse {
        $payload = $signature->extraire($request);
        if ($payload === null) {
            return response()->json(['error' => 'Accès refusé.'], 401);
        }

        // Aucune correspondance par email. Le lien est provisionné/révoqué
        // par l'équipe autorisée dans LA base de CET établissement.
        $hash = RequetePontSignee::identiteHash(
            $payload['issuer'], $payload['subject'], $payload['organization']
        );

        $lien = DB::table('mcp_external_identities')
            ->where('identity_hash', $hash)
            ->where('organization', $payload['organization'])
            ->whereNull('revoked_at')
            ->first();

        if (! $lien) {
            return response()->json(['error' => 'Accès refusé.'], 403);
        }

        $user = User::query()->whereKey($lien->user_id)->where('is_active', true)->first();
        if (! $user) {
            return response()->json(['error' => 'Accès refusé.'], 403);
        }

        // L'outil vérifie de nouveau les permissions effectives.
        $resultat = $lectures->executer($payload['tool'], $payload['arguments'], $user);
        if (array_key_exists('error', $resultat)) {
            return response()->json(['error' => 'Outil indisponible.'], 403);
        }

        return response()->json($resultat)->header('Cache-Control', 'no-store');
    }
}
