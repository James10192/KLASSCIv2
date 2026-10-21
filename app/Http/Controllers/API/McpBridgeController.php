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

        // Cet endpoint de bootstrap n'ouvre AUCUN accès scolaire. Une preuve
        // OAuth d'une organisation approvisionnée et la signature du gateway
        // ne suffisent qu'à demander un code court à usage unique. La liaison
        // se fait plus tard dans une session Laravel avec consentement.
        if ($payload['tool'] === 'create_pairing_challenge') {
            if (config('mcp.pairing.enabled') !== true) {
                return response()->json(['error' => 'Appairage indisponible.'], 403)
                    ->header('Cache-Control', 'no-store');
            }
            $code = $payload['arguments']['code'] ?? null;
            if (! is_string($code) || ! preg_match('/^[A-Za-z0-9_-]{43}$/D', $code)
                || ! is_array($payload['arguments']) || count($payload['arguments']) !== 1) {
                return response()->json(['error' => 'Demande invalide.'], 422)
                    ->header('Cache-Control', 'no-store');
            }

            // Une liaison préexistante, y compris révoquée, n'est pas
            // silencieusement remplacée par une nouvelle identité.
            if (DB::table('mcp_external_identities')->where('identity_hash', $hash)->exists()) {
                return response()->json(['error' => 'Liaison existante.'], 409)
                    ->header('Cache-Control', 'no-store');
            }

            $parIdentite = 'mcp:pair:'.hash('sha256', $hash);
            $parEcole = 'mcp:pair:org:'.hash('sha256', $payload['organization']);
            if (RateLimiter::tooManyAttempts($parIdentite, 3)
                || RateLimiter::tooManyAttempts($parEcole, 100)) {
                return response()->json(['error' => 'Trop de demandes.'], 429)
                    ->header('Cache-Control', 'no-store');
            }

            RateLimiter::hit($parIdentite, 600);
            RateLimiter::hit($parEcole, 3600);

            DB::table('mcp_link_challenges')->insert([
                'code_hash' => hash('sha256', $code),
                'identity_hash' => $hash,
                'organization' => $payload['organization'],
                'expires_at' => now()->addMinutes(10),
                'consumed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['accepted' => true, 'expires_in_seconds' => 600])
                ->header('Cache-Control', 'no-store');
        }

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
