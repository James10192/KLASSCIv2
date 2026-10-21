<?php

namespace App\Http\Controllers\Nanan;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gestion des autorisations MCP par le propriétaire du compte KLASSCI.
 *
 * Auth Laravel + session + CSRF sont imposés par routes/web.php.
 * Cette surface ne crée JAMAIS de liaison OAuth : l'appairage nécessite
 * une preuve OIDC et un consentement spécifique, non implémentés ici.
 */
final class McpConnectionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organisation = $this->organisation();

        // Ne pas divulguer l'empreinte d'identité, le sub de l'IdP, les
        // signatures, les secrets, les tokens ou les autres utilisateurs.
        $connexions = DB::table('mcp_external_identities')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('organization', $organisation)
            ->orderByDesc('id')
            ->get(['id', 'created_at', 'revoked_at'])
            ->map(fn ($lien) => [
                'id' => $lien->id,
                'created_at' => $lien->created_at,
                'revoked' => $lien->revoked_at !== null,
            ]);

        return response()->json(['connections' => $connexions])
            ->header('Cache-Control', 'no-store');
    }

    public function revoke(Request $request, int $identity): JsonResponse
    {
        $organisation = $this->organisation();

        // Les deux restrictions sont obligatoires sur l'UPDATE lui-même :
        // jamais de SELECT sans clause de propriété suivi d'un UPDATE global.
        $ownLink = DB::table('mcp_external_identities')
            ->where('id', $identity)
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('organization', $organisation);

        if (! (clone $ownLink)->exists()) {
            return response()->json(['error' => 'Connexion introuvable.'], 404)
                ->header('Cache-Control', 'no-store');
        }

        // Idempotent : un second clic n'active aucun accès.
        (clone $ownLink)->whereNull('revoked_at')->update([
            'revoked_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['revoked' => true])
            ->header('Cache-Control', 'no-store');
    }

    private function organisation(): string
    {
        $organisation = config('mcp.bridge.organization');

        // Le panneau ne doit pas s'ouvrir avant provisionnement de la
        // configuration locale propre à cet établissement.
        abort_unless(is_string($organisation) && $organisation !== '', 404);

        return $organisation;
    }
}
