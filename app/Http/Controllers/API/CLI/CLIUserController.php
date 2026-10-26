<?php

namespace App\Http\Controllers\API\CLI;

use App\Exceptions\LastActiveSuperAdminException;
use App\Exceptions\UserDeletionRejectedException;
use App\Http\Controllers\API\BaseApiController;
use App\Models\User;
use App\Rules\MotDePasseNonGenerique;
use App\Services\CLI\UserDeletionService;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use App\Services\Personnel\RoleAssignmentService;
use App\Services\UserManagementService;
use App\Listeners\AuditPermissionChange;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class CLIUserController extends BaseApiController
{
    /**
     * GET /api/cli/users — List user accounts
     */
    public function users(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }
        // Les abilities du token limitent son usage, mais ne remplacent jamais
        // le droit métier effectif de consulter les comptes.
        if (! $request->user()->checkPermissionTo('personnel.view', 'web')
            && ! $request->user()->checkPermissionTo('users.manage', 'web')) {
            return $this->errorResponse('Permission de consultation du personnel absente.', [], 403);
        }

        $query = User::query()->with('roles');

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

        $perPage = max(1, min((int) ($request->input('limit', 50)), 100));
        $paginated = $query->orderBy('id')->paginate($perPage);

        $users = collect($paginated->items())->map(function ($user) {
            $roles = $user->getRoleNames();
            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $roles->first() ?? '-', // Champ legacy : ne plus le traiter comme rôle unique.
                'roles' => $roles->values()->all(),
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
    public function userCreate(Request $request, RoleAssignmentService $roles): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }
        if (! $request->user()->checkPermissionTo('users.manage', 'web')
            || ! $request->user()->checkPermissionTo('personnel.roles.assign', 'web')) {
            return $this->errorResponse('Permissions de création et de rôle absentes.', [], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'username' => 'required|string|max:100|unique:users,username',
            'password' => ['required_without:password_hash', 'nullable', 'string', 'min:12', new MotDePasseNonGenerique],
            'password_hash' => 'required_without:password|nullable|string|max:255',
            'role' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'must_change_password' => 'nullable|boolean',
            'motif' => 'required|string|min:3|max:1000',
        ]);
        if ($denial = $this->assertRoleAssignable($validated['role'])) {
            return $denial;
        }
        if ($validated['role'] === 'serviceTechnique'
            || ($validated['role'] === 'superAdmin'
                && ! $request->user()->checkPermissionTo('superadmin.accounts.create', 'web'))) {
            return $this->errorResponse('Création de compte privilégié non autorisée.', [], 403);
        }

        try {
            $attributes = [
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'username' => $validated['username'],
                'password' => isset($validated['password_hash'])
                    ? $validated['password_hash']
                    : Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
                'must_change_password' => $validated['must_change_password']
                    ?? ! isset($validated['password_hash']),
                'created_by' => $request->user()->id,
            ];

            if ($validated['role'] === 'superAdmin') {
                // Impossible d'invoquer change() pour promouvoir une cible :
                // seule cette opération insère un NOUVEAU compte et l'audite.
                $user = $roles->createInitialSuperAdmin(
                    $request->user(), $attributes, $validated['motif']
                );
            } else {
                $user = DB::transaction(function () use ($request, $validated, $roles, $attributes): User {
                    $user = User::create($attributes);
                    $roles->change(
                        $request->user(), $user->id, 'add',
                        $validated['role'], $validated['motif']
                    );
                    return $user;
                }, 3);
            }

            return $this->successResponse([
                'user_id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $validated['role'],
            ], 'Compte créé avec rôle et audit atomiques.');
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), [], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('CLI: account creation rolled back', ['exception' => get_class($e)]);
            return $this->errorResponse('Création annulée sans compte partiel.', [], 500);
        }
    }

    /**
     * POST /api/cli/user/{id}/reset-password-expiry — Mark password as just changed
     */
    public function userResetPasswordExpiry(
        Request $request,
        $id,
        SuperAdminLifecycleGuard $lifecycle,
        UserManagementService $management,
    ): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        if (! $request->user()->checkPermissionTo('personnel.users.password.reset', 'web')) {
            return $this->errorResponse('Permission de réinitialisation absente.', [], 403);
        }
        $caller = $request->user();
        try {
            $user = $lifecycle->updateUser(
                (int) $id,
                [
                    'password_changed_at' => now(),
                    'must_change_password' => false,
                ],
                authorize: function (User $target) use ($caller, $management): void {
                    if (! $management->canManage($caller, $target)) {
                        throw new AuthorizationException('Compte hors de votre périmètre de gestion.');
                    }
                    // Classification de la cible, jamais habilitation via nom de rôle.
                    if ($target->getRoleNames()->intersect(['superAdmin', 'serviceTechnique'])->isNotEmpty()) {
                        throw new AuthorizationException('Compte sensible : changement de mot de passe refusé par la CLI.');
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
    public function userCredentials(Request $request, $id, UserManagementService $management): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }
        if (! $request->user()->checkPermissionTo('personnel.users.credentials.export', 'web')) {
            return $this->errorResponse('Permission d’export des identifiants absente.', [], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse("User #{$id} not found", [], 404);
        }

        $caller = $request->user();
        // L'export de l'empreinte est limité au périmètre de gestion effectif.
        if (! $management->canManage($caller, $user)) {
            return $this->errorResponse('Compte hors de votre périmètre de gestion.', [], 403);
        }
        if ($user->getRoleNames()->intersect(['superAdmin', 'serviceTechnique'])->isNotEmpty()) {
            Log::warning('CLI: credentials export DENIED on privileged target', [
                'target_user_id' => $user->id,
                'caller_user_id' => $caller->id,
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse(
                'Export interdit pour un compte sensible, quel que soit le rôle du demandeur.',
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
            $motif = $request->validate(['motif' => 'required|string|min:3|max:1000'])['motif'];
            $result = $deletion->delete((int) $id, $request->user(), $motif);

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
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Une donnée invalide ne doit jamais devenir une erreur 500.
            throw $e;
        } catch (\Exception $e) {
            Log::error('CLI: user deletion failed', [
                'actor_id' => $request->user()?->id,
                'target_user_id' => (int) $id,
                'exception_class' => get_class($e),
            ]);
            return $this->errorResponse('Operation failed. Check server logs for details.', [], 500);
        }
    }

    public function userResetPassword(
        Request $request, $id, SuperAdminLifecycleGuard $lifecycle,
        UserManagementService $management, AuditPermissionChange $audit
    ): JsonResponse {
        $caller = $request->user();
        if (! $caller->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }
        if (! $caller->checkPermissionTo('personnel.users.password.reset', 'web')) {
            return $this->errorResponse('Permission de réinitialisation absente.', [], 403);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', new MotDePasseNonGenerique],
        ]);

        try {
            // Relecture et verrouillage atomiques : une promotion concurrente
            // ne peut pas rendre caduque le contrôle de cible sensible.
            $user = $lifecycle->updateUser(
                (int) $id,
                [
                    'password' => Hash::make($validated['password']),
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                ],
                afterUpdate: function (User $target) use ($caller, $audit): void {
                    // Audit durable dans la même transaction, sans secret.
                    $audit->enregistrerDecisionDeCompte(
                        $target,
                        ['security_action' => 'before_cli_password_reset'],
                        ['security_action' => 'cli_password_reset'],
                        $caller->id,
                        'Réinitialisation du mot de passe via API CLI'
                    );
                },
                authorize: function (User $target) use ($caller, $management): void {
                    if (! $management->canManage($caller, $target)) {
                        throw new AuthorizationException('Compte hors de votre périmètre de gestion.');
                    }
                    if ($target->getRoleNames()->intersect(['superAdmin', 'serviceTechnique'])->isNotEmpty()) {
                        throw new AuthorizationException('Compte sensible : réinitialisation refusée par la CLI.');
                    }
                },
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), [], 403);
        } catch (ModelNotFoundException) {
            return $this->errorResponse('Compte introuvable.', [], 404);
        } catch (\Throwable $e) {
            Log::error('CLI: password reset transaction rolled back', [
                'target_user_id' => (int) $id,
                'exception_class' => get_class($e),
            ]);
            return $this->errorResponse('Réinitialisation annulée sans modification partielle.', [], 500);
        }

        Log::info('CLI: password reset', [
            'target_user_id' => $user->id,
            'caller_user_id' => $caller->id,
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
    public function userSetRole(Request $request, $id, RoleAssignmentService $roles): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }
        $data = $request->validate([
            'role' => ['required', 'string'],
            'mode' => ['nullable', 'in:add,remove,replace'],
            'old_role' => ['required_if:mode,replace', 'nullable', 'string'],
            'expected_roles' => ['required_if:mode,replace', 'array'],
            'expected_roles.*' => ['string'],
            'motif' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        $mode = $data['mode'] ?? 'add'; // Défaut non destructif.
        if ($denial = $this->assertRoleAssignable($data['role'])) {
            return $denial;
        }
        try {
            $result = $roles->change(
                $request->user(), (int) $id, $mode, $data['role'],
                $data['motif'], $data['old_role'] ?? null, $data['expected_roles'] ?? []
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), [], 403);
        } catch (ModelNotFoundException) {
            return $this->errorResponse('Compte introuvable.', [], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('CLI: role transaction rolled back', [
                'user_id' => (int) $id, 'exception' => get_class($e),
            ]);
            return $this->errorResponse('Modification annulée, aucun rôle partiellement modifié.', [], 500);
        }
        return $this->successResponse([
            'user_id' => (int) $id,
            'mode' => $mode,
            'roles_before' => $result['before'],
            'roles_after' => $result['after'],
        ], 'Rôles modifiés et audités.');
    }

    private function assertRoleAssignable(string $role): ?JsonResponse
    {
        // Un appel d'affectation ne crée jamais implicitement un rôle.
        return Role::query()->where('name', $role)->where('guard_name', 'web')->exists()
            ? null
            : $this->errorResponse('Rôle inconnu dans cet établissement.', [], 422);
    }
}
