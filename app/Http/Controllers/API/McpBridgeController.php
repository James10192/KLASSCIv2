<?php

namespace App\Http\Controllers\API;

use App\Domain\Assistant\Mcp\CatalogueLectureExterne;
use App\Domain\Assistant\Mcp\RequetePontSignee;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

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

        // Après authentification HMAC + liaison à un compte local actif,
        // limiter PAR UTILISATEUR, pas par IP partagée de la passerelle.
        // Un utilisateur bruyant ne doit jamais épuiser le quota des autres.
        $quota = max(1, min(120, (int) config('mcp.bridge.requests_per_minute', 30)));
        $quotaKey = 'mcp:rate:'.hash('sha256', $payload['organization'].'|'.$user->id);
        if (RateLimiter::tooManyAttempts($quotaKey, $quota)) {
            return response()->json(['error' => 'Limite de lectures atteinte.'], 429)
                ->header('Retry-After', (string) max(1, RateLimiter::availableIn($quotaKey)))
                ->header('Cache-Control', 'no-store');
        }
        RateLimiter::hit($quotaKey, 60);

        // Découverte interne UNIQUEMENT, jamais exposée comme outil au modèle.
        // Lire le catalogue Nanan local à CET établissement et CE compte :
        // les permissions ont pu changer depuis l'émission du JWT.
        if ($payload['tool'] === 'list_authorized_tools') {
            $schemas = $lectures->schemas($user);

            return response()->json([
                'tools' => array_values(array_map(
                    fn (array $schema) => $schema['name'],
                    $schemas
                )),
            ])->header('Cache-Control', 'no-store');
        }

        // Profil de l'identité LOCALE, seulement après preuve JWT, signature
        // HMAC, lien non révoqué et compte actif. Aucune recherche par e-mail.
        if ($payload['tool'] === 'get_connected_profile') {
            // Identifiant stable entre les tokens, reconnexions et rotations du
            // secret HMAC du pont. Ce hash pseudonyme n'est PAS une
            // authentification ni une donnée pour retrouver un compte.
            $opaqueId = hash('sha256', "klassci:nanan-profile:v1\0".$hash);

            return response()->json([
                'id' => 'kl_'.substr($opaqueId, 0, 24),
                'name' => (string) $user->name,
            ])->header('Cache-Control', 'no-store');
        }

        // L'outil vérifie de nouveau les permissions effectives.
        $resultat = $lectures->executer($payload['tool'], $payload['arguments'], $user);
        if (array_key_exists('error', $resultat)) {
            return response()->json(['error' => 'Outil indisponible.'], 403);
        }

        return response()->json($resultat)->header('Cache-Control', 'no-store');
    }
}
