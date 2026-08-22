<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

/**
 * Permissions accordees directement a UN utilisateur, sans passer par son role.
 *
 * Chaque ecole configure ses roles comme elle l'entend : resynchroniser le
 * registre pour depanner ou tester ecraserait ses choix. Ce point d'entree
 * permet d'ouvrir un acces precis a un compte precis, puis de le retirer, sans
 * jamais toucher a la configuration de l'etablissement.
 *
 * Les permissions accordees ici s'ajoutent a celles du role. Elles sont
 * visibles dans l'ecran des roles, et retirables par la meme voie.
 */
class CLIUserPermissionController extends BaseApiController
{
    /**
     * Roles qu'on ne modifie pas depuis le CLI : le super administrateur
     * possede deja tout, et le service technique est un acces de secours.
     */
    private const ROLES_PROTEGES = ['superAdmin', 'serviceTechnique'];

    /**
     * GET /api/cli/user/{id}/permissions
     */
    public function index(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $user = User::find($id);
        if (! $user) {
            return $this->errorResponse("Utilisateur {$id} introuvable.", ['code' => 'USER_NOT_FOUND'], 404);
        }

        return $this->successResponse([
            'user_id' => $user->id,
            'username' => $user->username,
            'roles' => $user->getRoleNames()->all(),
            'permissions_directes' => $user->getDirectPermissions()->pluck('name')->sort()->values()->all(),
            'permissions_du_role' => $user->getPermissionsViaRoles()->pluck('name')->sort()->values()->all(),
        ]);
    }

    /**
     * POST /api/cli/user/{id}/permissions
     *
     * Corps : permissions[] = noms, mode = grant|revoke (grant par defaut).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'required|string|max:191',
            'mode' => 'nullable|in:grant,revoke',
        ]);

        $user = User::find($id);
        if (! $user) {
            return $this->errorResponse("Utilisateur {$id} introuvable.", ['code' => 'USER_NOT_FOUND'], 404);
        }

        $protege = array_intersect($user->getRoleNames()->all(), self::ROLES_PROTEGES);
        if ($protege !== []) {
            return $this->errorResponse(
                'Compte '.implode(' et ', $protege).' : ses permissions ne se modifient pas depuis le CLI.',
                ['code' => 'PROTECTED_ROLE'],
                422
            );
        }

        $mode = $valide['mode'] ?? 'grant';
        $demandees = array_values(array_unique($valide['permissions']));

        // Une permission inconnue est signalee, jamais creee : inventer un nom
        // donnerait un acces qui ne correspond a aucune garde du code.
        $connues = Permission::whereIn('name', $demandees)->pluck('name')->all();
        $inconnues = array_values(array_diff($demandees, $connues));

        if ($connues === []) {
            return $this->errorResponse(
                'Aucune de ces permissions n existe : '.implode(', ', $inconnues),
                ['code' => 'UNKNOWN_PERMISSIONS', 'inconnues' => $inconnues],
                422
            );
        }

        $avant = $user->getDirectPermissions()->pluck('name')->sort()->values()->all();

        foreach ($connues as $nom) {
            $mode === 'revoke'
                ? $user->revokePermissionTo($nom)
                : $user->givePermissionTo($nom);
        }

        $user->forgetCachedPermissions();
        $apres = $user->fresh()->getDirectPermissions()->pluck('name')->sort()->values()->all();

        Log::warning('CLI: permissions directes modifiees sur un compte', [
            'user_id' => $user->id,
            'username' => $user->username,
            'mode' => $mode,
            'permissions' => $connues,
            'par' => $request->user()->id,
        ]);

        return $this->successResponse([
            'user_id' => $user->id,
            'username' => $user->username,
            'mode' => $mode,
            'appliquees' => $connues,
            'inconnues' => $inconnues,
            'permissions_directes_avant' => $avant,
            'permissions_directes_apres' => $apres,
        ], count($connues).' permission(s) '.($mode === 'revoke' ? 'retiree(s)' : 'accordee(s)').'.');
    }
}
