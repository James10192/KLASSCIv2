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

        $validRoles = ['superAdmin', 'admin', 'secretaire', 'responsableScolarite', 'serviceScolarite', 'agentInscription', 'coordinateur', 'directeurEtudes', 'enseignant',
                        'etudiant', 'parent', 'comptable', 'caissier', 'teacher'];

        if (!in_array($validated['role'], $validRoles)) {
            return $this->errorResponse("Invalid role '{$validated['role']}'. Valid: " . implode(', ', $validRoles), [], 422);
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
}
