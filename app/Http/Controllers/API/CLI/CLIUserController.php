<?php

namespace App\Http\Controllers\API\CLI;

use App\Exceptions\LastActiveSuperAdminException;
use App\Exceptions\UserDeletionRejectedException;
use App\Http\Controllers\API\BaseApiController;
use App\Models\User;
use App\Services\CLI\UserDeletionService;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class CLIUserController extends BaseApiController
{
    /**
     * Roles attribuables par le CLI. Partagee entre la creation et le
     * changement de role : deux listes divergeraient a la premiere evolution
     * de l'organigramme.
     */
    private const VALID_ROLES = [
        'superAdmin', 'admin', 'secretaire', 'responsableScolarite', 'serviceScolarite',
        'agentInscription', 'chargeCommunication', 'coordinateur', 'directeurEtudes', 'enseignant',
        'etudiant', 'parent', 'comptable', 'caissier', 'teacher',
    ];

    /**
     * GET /api/cli/users — List user accounts
     */
    public function users(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $query = User::query();

        if ($role = $request->input('role')) {
            $query->role($role);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) ($request->input('limit', 50)), 100);
        $paginated = $query->orderBy('id')->paginate($perPage);

        $users = collect($paginated->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first() ?? '-',
                'is_active' => $user->is_active,
                'created_at' => $user->created_at?->toIso8601String(),
            ];
        });

        return $this->successResponse([
            'users' => $users,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ], 'User accounts');
    }

    /**
     * POST /api/cli/user/create — Create a user with a role
     */
    public function userCreate(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        // password_hash permet de recreer un compte a l'identique sur un autre
        // tenant (cf. userCredentials) sans jamais connaitre le mot de passe en
        // clair. L'un des deux champs est requis, jamais les deux.
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'username' => 'required|string|max:100|unique:users,username',
            'password' => 'required_without:password_hash|nullable|string|min:12',
            'password_hash' => 'required_without:password|nullable|string|max:255',
            'role' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'must_change_password' => 'nullable|boolean',
        ]);

        if ($roleError = $this->assertRoleAssignable($validated['role'])) {
            return $roleError;
        }

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'username' => $validated['username'],
                'password' => isset($validated['password_hash'])
                    ? $validated['password_hash']
                    : Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
                // Un compte cloné garde le mot de passe de son tenant d'origine :
                // rien à réinitialiser, sinon l'utilisateur serait bloqué.
                'must_change_password' => $validated['must_change_password']
                    ?? !isset($validated['password_hash']),
                'created_by' => $request->user()->id,
            ]);

            $user->assignRole($validated['role']);

            return $this->successResponse([
                'user_id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $validated['role'],
            ], "User '{$user->name}' created with role '{$validated['role']}'");
        } catch (\Exception $e) {
            Log::error('CLI: user creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Operation failed. Check server logs for details.', [], 500);
        }
    }

    /**
     * POST /api/cli/user/{id}/reset-password-expiry — Mark password as just changed
     */
    public function userResetPasswordExpiry(
        Request $request,
        $id,
        SuperAdminLifecycleGuard $lifecycle,
    ): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $caller = $request->user();
        try {
            $user = $lifecycle->updateUser(
                (int) $id,
                [
                    'password_changed_at' => now(),
                    'must_change_password' => false,
                ],
                authorize: function (User $target) use ($caller): void {
                    if ($target->hasAnyRole(['superAdmin', 'serviceTechnique']) && !$caller->hasRole('superAdmin')) {
                        throw new AuthorizationException('Only a superAdmin can update privileged password state');
                    }
                },
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), [], 403);
        } catch (ModelNotFoundException) {
            return $this->errorResponse("User #{$id} not found", [], 404);
        }

        return $this->successResponse([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'password_changed_at' => $user->password_changed_at->toIso8601String(),
            'must_change_password' => false,
        ], "Password expiry reset for {$user->name}");
    }

    /**
     * POST /api/cli/user/{id}/delete — Soft-delete a user
     */
    /**
     * GET /api/cli/user/{id}/credentials — Exporte l'identite d'un compte,
     * empreinte de mot de passe comprise, pour le recreer a l'identique sur un
     * autre tenant (meme personne employee par deux etablissements).
     *
     * L'empreinte bcrypt est renvoyee telle quelle : elle n'est pas reversible,
     * mais elle reste une donnee sensible. D'ou les memes garde-fous que
     * reset-password (cible privilegiee interdite hors superAdmin) et un log
     * systematique. L'empreinte n'est jamais ecrite dans les logs.
     */
    public function userCredentials(Request $request, $id): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse("User #{$id} not found", [], 404);
        }

        $caller = $request->user();
        if ($user->hasAnyRole(['superAdmin', 'serviceTechnique']) && !$caller->hasRole('superAdmin')) {
            Log::warning('CLI: credentials export DENIED on privileged target', [
                'target_user_id' => $user->id,
                'caller_user_id' => $caller->id,
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse(
                'Cannot export credentials of a privileged user (superAdmin or serviceTechnique) without superAdmin caller.',
                [],
                403
            );
        }

        Log::info('CLI: credentials exported', [
            'target_user_id' => $user->id,
            'target_roles' => $user->getRoleNames()->toArray(),
            'caller_user_id' => $caller->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse([
            'user_id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => (bool) $user->is_active,
            'must_change_password' => (bool) $user->must_change_password,
            'roles' => $user->getRoleNames()->toArray(),
            'password_hash' => $user->password,
        ], "Credentials exported for '{$user->username}'");
    }

    public function userDelete(Request $request, $id, UserDeletionService $deletion): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        try {
            $result = $deletion->delete((int) $id, $request->user());

            return $result['status'] === 200
                ? $this->successResponse($result['data'], $result['message'])
                : $this->errorResponse($result['message'], [], $result['status']);
        } catch (LastActiveSuperAdminException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        } catch (UserDeletionRejectedException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), [], 403);
        } catch (ModelNotFoundException) {
            return $this->errorResponse("User #{$id} not found", [], 404);
        } catch (\Exception $e) {
            Log::error('CLI: user deletion failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Operation failed. Check server logs for details.', [], 500);
        }
    }

    public function userResetPassword(Request $request, $id): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse("User #{$id} not found", [], 404);
        }

        // Guard target = superAdmin/serviceTechnique (audit sécurité 2026-05-21).
        // Avant ce fix : un token cli:admin volé pouvait reset le mot de passe
        // d'un superAdmin et compromettre tout le tenant. Seul un superAdmin
        // (via son propre token) peut désormais reset un compte privilégié.
        $caller = $request->user();
        $targetIsPrivileged = $user->hasAnyRole(['superAdmin', 'serviceTechnique']);
        $callerIsSuperAdmin = $caller->hasRole('superAdmin');

        if ($targetIsPrivileged && !$callerIsSuperAdmin) {
            Log::warning('CLI: reset-password DENIED on privileged target', [
                'target_user_id' => $user->id,
                'target_roles' => $user->getRoleNames()->toArray(),
                'caller_user_id' => $caller->id,
                'caller_roles' => $caller->getRoleNames()->toArray(),
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse(
                'Cannot reset password of a privileged user (superAdmin or serviceTechnique) without superAdmin caller.',
                [],
                403
            );
        }

        $validated = $request->validate(['password' => 'required|string|min:8']);

        $user->update([
            'password'             => bcrypt($validated['password']),
            'must_change_password' => false,
            'password_changed_at'  => now(),
        ]);

        // Audit log enrichi pour traçabilité forensique (les tokens du target
        // sont automatiquement révoqués par User::booted, voir audit Phase B1).
        Log::info('CLI: password reset', [
            'target_user_id' => $user->id,
            'target_roles' => $user->getRoleNames()->toArray(),
            'caller_user_id' => $caller->id,
            'caller_roles' => $caller->getRoleNames()->toArray(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->successResponse(
            ['name' => $user->name, 'email' => $user->email, 'username' => $user->username],
            'Password reset successfully.'
        );
    }
/**
     * POST /api/cli/user/{id}/role — Change or add a role on an existing account.
     *
     * Le CLI ne savait attribuer un role qu'a la creation : corriger un compte
     * deja cree imposait de passer par l'interface web, donc de disposer d'un
     * compte superAdmin sur le tenant.
     *
     * Body: { role: string, mode?: 'replace'|'add' }
     */
    public function userSetRole(Request $request, $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $user = User::find($id);
        if (! $user) {
            return $this->errorResponse("User #{$id} not found", [], 404);
        }

        $validated = $request->validate([
            'role' => 'required|string',
            'mode' => 'nullable|in:replace,add',
        ]);
        $mode = $validated['mode'] ?? 'replace';
        $role = $validated['role'];

        if ($roleError = $this->assertRoleAssignable($role)) {
            return $roleError;
        }

        // Memes gardes que reset-password (audit securite 2026-05-21), dans les
        // deux sens : on ne touche pas a un compte privilegie, et surtout on
        // n'en fabrique pas un. Sans le second test, un jeton cli:admin vole
        // suffirait a se promouvoir superAdmin, ce qui serait pire qu'un reset.
        $caller = $request->user();
        $callerIsSuperAdmin = $caller->hasRole('superAdmin');
        $targetIsPrivileged = $user->hasAnyRole(['superAdmin', 'serviceTechnique']);
        $grantIsPrivileged = in_array($role, ['superAdmin', 'serviceTechnique'], true);

        if (($targetIsPrivileged || $grantIsPrivileged) && ! $callerIsSuperAdmin) {
            Log::warning('CLI: set-role DENIED on privileged target or grant', [
                'target_user_id' => $user->id,
                'target_roles' => $user->getRoleNames()->toArray(),
                'requested_role' => $role,
                'caller_user_id' => $caller->id,
                'caller_roles' => $caller->getRoleNames()->toArray(),
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse(
                'Cannot change roles of, or grant, a privileged role (superAdmin or serviceTechnique) without superAdmin caller.',
                [],
                403
            );
        }

        $avant = $user->getRoleNames()->toArray();

        // Retirer le dernier superAdmin actif fermerait le tenant a clef.
        // Meme definition que SuperAdminLifecycleGuard : un autre superAdmin
        // actif doit subsister ailleurs.
        if ($mode === 'replace' && in_array('superAdmin', $avant, true) && ! $grantIsPrivileged) {
            $autreSuperAdminActif = User::role('superAdmin')
                ->where('users.is_active', true)
                ->where('users.id', '!=', $user->id)
                ->exists();

            if (! $autreSuperAdminActif) {
                return $this->errorResponse(
                    "Refus : #{$user->id} est le dernier superAdmin actif du tenant. Promouvoir un autre compte avant de lui retirer ce role.",
                    [],
                    422
                );
            }
        }

        $mode === 'replace' ? $user->syncRoles([$role]) : $user->assignRole($role);

        $apres = $user->fresh()->getRoleNames()->toArray();

        Log::info('CLI: role changed', [
            'target_user_id' => $user->id,
            'mode' => $mode,
            'roles_before' => $avant,
            'roles_after' => $apres,
            'caller_user_id' => $caller->id,
            'caller_roles' => $caller->getRoleNames()->toArray(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->successResponse([
            'user_id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'mode' => $mode,
            'roles_before' => $avant,
            'roles_after' => $apres,
        ], "Roles updated for '{$user->name}': ".implode(', ', $apres));
    }

    private function assertRoleAssignable(string $role): ?JsonResponse
    {
        $exists = Role::where('name', $role)->where('guard_name', 'web')->exists();
        if ($exists) {
            return null;
        }

        if (in_array($role, self::VALID_ROLES, true)) {
            Role::findOrCreate($role, 'web');

            return null;
        }

        return $this->errorResponse(
            "Role '{$role}' not found. Create it as a custom role first.",
            [],
            422
        );
    }
}
