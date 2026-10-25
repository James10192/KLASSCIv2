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
        $mode = $valide['mode'] ?? 'grant';
        $demandees = array_values(array_unique($valide['permissions']));

        // Les droits directs sont permanents : un token CLI et le seul droit
        // personnel.manage ne sauraient octroyer ce type de délégation.
        $acteur = User::query()->findOrFail($request->user()->id);
        foreach (['personnel.manage', 'permissions.individuels.permanents.manage'] as $nom) {
            if (! $acteur->checkPermissionTo($nom, 'web')) {
                return $this->errorResponse('Délégation permanente non autorisée.', ['code' => 'ACCES_REFUSE'], 403);
            }
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($acteur, $id, $mode, $demandees): JsonResponse {
            // Même ordre de verrous que les mutations du personnel.
            \Spatie\Permission\Models\Role::query()
                ->where('name', 'superAdmin')->where('guard_name', 'web')
                ->lockForUpdate()->first();

            $user = User::query()->with('roles')->whereKey($id)->lockForUpdate()->first();
            if (! $user) {
                return $this->errorResponse("Utilisateur {$id} introuvable.", ['code' => 'USER_NOT_FOUND'], 404);
            }
            if (! app(\App\Services\UserManagementService::class)->canManage($acteur, $user)) {
                return $this->errorResponse(
                    'Compte protégé ou hors du périmètre de gestion.',
                    ['code' => 'PROTECTED_ROLE'], 403
                );
            }
            if ($mode === 'grant' && ! $user->is_active) {
                return $this->errorResponse('Un compte inactif ne peut recevoir de nouveaux droits.', [], 403);
            }

            $connues = Permission::query()->where('guard_name', 'web')
                ->whereIn('name', $demandees)->pluck('name')->all();
            $inconnues = array_values(array_diff($demandees, $connues));
            if ($connues === []) {
                return $this->errorResponse(
                    'Aucune permission connue pour le guard web.',
                    ['code' => 'UNKNOWN_PERMISSIONS', 'inconnues' => $inconnues], 422
                );
            }
            foreach ($connues as $nom) {
                if (! $acteur->checkPermissionTo($nom, 'web')) {
                    return $this->errorResponse(
                        'Vous ne pouvez modifier une permission hors de votre périmètre.',
                        ['code' => 'ACCES_REFUSE', 'permission' => $nom], 403
                    );
                }
            }

            $avant = $user->getDirectPermissions()->pluck('name')->sort()->values()->all();
            foreach ($connues as $nom) {
                $mode === 'revoke'
                    ? $user->revokePermissionTo($nom)
                    : $user->givePermissionTo($nom);
            }
            $user->forgetCachedPermissions();
            $apres = $user->fresh()->getDirectPermissions()->pluck('name')->sort()->values()->all();

            // Trace durable dans la MEME transaction que les pivots.
            app(\App\Listeners\AuditPermissionChange::class)->enregistrerDecisionDeCompte(
                $user, ['permissions_directes' => $avant],
                ['permissions_directes' => $apres], $acteur->id,
                'Modification individuelle des droits depuis la CLI ('.$mode.')'
            );

            Log::warning('CLI: permissions directes modifiees sur un compte', [
                'user_id' => $user->id,
                'mode' => $mode,
                'permissions' => $connues,
                'par' => $acteur->id,
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
        }, 3);
    }
}
