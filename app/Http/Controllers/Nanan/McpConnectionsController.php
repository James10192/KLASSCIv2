<?php

namespace App\Http\Controllers\Nanan;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

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

    /**
     * Lier un identifiant OAuth déjà prouvé par le gateway MCP :
     * - le code 256 bits vient d'un vrai JWT Auth0 signé ;
     * - la session Laravel fournit l'utilisateur LOCAL, jamais un email ;
     * - le consentement est explicite ;
     * - le challenge est consommé sous verrou SQL, une seule fois.
     */
    /**
     * Contrôle hors bande d'un administrateur local DISTINCT du demandeur.
     *
     * Une saisie de code seule ne prouve pas l'identité de son détenteur :
     * l'administrateur doit vérifier directement le demandeur, son compte
     * Auth0 et son appartenance à l'école avant cette approbation.
     *
     * Pas de liaison créée ici. L'employé devra lui-même donner son
     * consentement depuis sa propre session Laravel.
     */
    public function approve(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        abort_unless(config('mcp.pairing.enabled') === true
            && config('mcp.bridge.enabled') === true
            && config('mcp.read_only.enabled') === true, 404);

        $organisation = $this->organisation();
        $approver = $request->user();
        abort_unless($approver && $approver->is_active
            && $approver->can('mcp.connections.approve'), 403);

        $validation = $request->validate([
            'code' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{43}$/D'],
            'user_id' => ['required', 'integer', 'min:1'],
            'identity_verified' => ['required', 'accepted'],
        ]);

        $target = \App\Models\User::query()
            ->whereKey($validation['user_id'])->where('is_active', true)->first();
        abort_unless($target
            && $target->getAuthIdentifier() !== $approver->getAuthIdentifier()
            && $target->can('mcp.connections.connect'), 403);

        $approved = DB::transaction(function () use (
            $validation, $organisation, $approver, $target
        ): bool {
            // Revérification au moment de l'écriture : un droit révoqué
            // durant la requête ne doit pas permettre une approbation.
            $lockedApprover = \App\Models\User::query()->whereKey($approver->id)
                ->lockForUpdate()->first();
            $lockedTarget = \App\Models\User::query()->whereKey($target->id)
                ->lockForUpdate()->first();
            abort_unless($lockedApprover?->is_active
                && $lockedApprover->can('mcp.connections.approve')
                && $lockedTarget?->is_active
                && $lockedTarget->can('mcp.connections.connect')
                && $lockedApprover->id !== $lockedTarget->id, 403);

            $challenge = DB::table('mcp_link_challenges')
                ->where('code_hash', hash('sha256', $validation['code']))
                ->where('organization', $organisation)
                ->whereNull('consumed_at')
                ->whereNull('approved_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();
            if (! $challenge) {
                return false;
            }
            if (DB::table('mcp_external_identities')
                ->where('identity_hash', $challenge->identity_hash)->exists()) {
                return false;
            }

            DB::table('mcp_link_challenges')
                ->where('id', $challenge->id)
                ->whereNull('approved_at')
                ->update([
                    'approved_user_id' => $target->getAuthIdentifier(),
                    'approved_by_user_id' => $approver->getAuthIdentifier(),
                    'approved_at' => now(),
                    'updated_at' => now(),
                ]);

            return true;
        });

        if (! $approved) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Demande expirée, utilisée ou déjà approuvée.'], 422)
                    ->header('Cache-Control', 'no-store');
            }

            return redirect()->route('nanan.mcp.review')->with('approval_error',
                'Code expiré, déjà utilisé ou déjà approuvé.');
        }

        if ($request->expectsJson()) {
            return response()->json(['approved' => true])
                ->header('Cache-Control', 'no-store');
        }

        return redirect()->route('nanan.mcp.review')->with('approval_success',
            'Demande vérifiée et approuvée. Le personnel doit encore se connecter à son compte et accepter.');
    }

    public function review(Request $request): \Illuminate\View\View
    {
        abort_unless(config('mcp.pairing.enabled') === true
            && config('mcp.bridge.enabled') === true
            && config('mcp.read_only.enabled') === true, 404);
        $this->organisation();
        abort_unless($request->user()?->is_active
            && $request->user()->can('mcp.connections.approve'), 403);

        return view('nanan.mcp.review');
    }

    public function claim(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (config('mcp.pairing.enabled') !== true
            || config('mcp.bridge.enabled') !== true
            || config('mcp.read_only.enabled') !== true) {
            abort(404);
        }

        $organisation = $this->organisation();
        $personne = $request->user();

        // Permission métier dédiée au rattachement MCP, révocable sur chaque compte.
        if (! $personne || ! $personne->is_active
            || ! $personne->can('mcp.connections.connect')) {
            abort(403);
        }

        $validation = $request->validate([
            'code' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{43}$/D'],
            'consent' => ['required', 'accepted'],
        ]);

        $codeHash = hash('sha256', $validation['code']);
        try {
            $accepted = DB::transaction(function () use ($codeHash, $personne, $organisation): bool {
                $lockedPersonne = \App\Models\User::query()->whereKey($personne->id)
                    ->lockForUpdate()->first();
                abort_unless($lockedPersonne?->is_active
                    && $lockedPersonne->can('mcp.connections.connect'), 403);

                $challenge = DB::table('mcp_link_challenges')
                    ->where('code_hash', $codeHash)
                    ->where('organization', $organisation)
                    ->whereNull('consumed_at')
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first();

                if (! $challenge
                    || $challenge->approved_at === null
                    || (int) $challenge->approved_user_id !== (int) $personne->getAuthIdentifier()
                    || (int) $challenge->approved_by_user_id === (int) $personne->getAuthIdentifier()) {
                    return false;
                }

                // L'approbation perd son effet si l'approbateur a été désactivé
                // ou si son habilitation MCP a été révoquée entre-temps.
                $validApprover = \App\Models\User::query()
                    ->whereKey($challenge->approved_by_user_id)->where('is_active', true)->first();
                if (! $validApprover?->can('mcp.connections.approve')) {
                    return false;
                }

                // Aucune substitution silencieuse à un utilisateur existant,
                // pas même si une liaison précédente est révoquée.
                if (DB::table('mcp_external_identities')
                    ->where('identity_hash', $challenge->identity_hash)->exists()) {
                    return false;
                }

                DB::table('mcp_external_identities')->insert([
                    'identity_hash' => $challenge->identity_hash,
                    'organization' => $organisation,
                    'user_id' => $personne->getAuthIdentifier(),
                    'revoked_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('mcp_link_challenges')
                    ->where('id', $challenge->id)
                    ->whereNull('consumed_at')
                    ->update(['consumed_at' => now(), 'updated_at' => now()]);

                return true;
            });
        } catch (QueryException) {
            // L'index UNIQUE sur identity_hash protège aussi les courses.
            $accepted = false;
        }

        if (! $accepted) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Code expiré, utilisé ou liaison existante.'], 422)
                    ->header('Cache-Control', 'no-store');
            }

            return redirect()->route('nanan.mcp.connect')->with('pairing_error',
                'Code invalide, expiré, déjà utilisé ou compte déjà lié.');
        }

        if ($request->expectsJson()) {
            return response()->json(['linked' => true])->header('Cache-Control', 'no-store');
        }

        return redirect()->route('nanan.mcp.connect')->with('pairing_success',
            'Compte connecté. Les autorisations KLASSCI restent appliquées à chaque consultation.');
    }

    public function connect(Request $request): \Illuminate\View\View
    {
        abort_unless(config('mcp.pairing.enabled') === true, 404);
        $this->organisation();
        abort_unless($request->user()?->is_active
            && $request->user()->can('mcp.connections.connect'), 403);

        return view('nanan.mcp.connect');
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
