<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\User;
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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'username' => 'required|string|max:100|unique:users,username',
            'password' => 'required|string|min:12',
            'role' => 'required|string',
            'phone' => 'nullable|string|max:20',
        ]);

        $validRoles = ['superAdmin', 'admin', 'secretaire', 'coordinateur', 'enseignant',
                        'etudiant', 'parent', 'comptable', 'caissier', 'teacher'];

        if (!in_array($validated['role'], $validRoles)) {
            return $this->errorResponse("Invalid role '{$validated['role']}'. Valid: " . implode(', ', $validRoles), [], 422);
        }

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
                'must_change_password' => true,
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
    public function userResetPasswordExpiry(Request $request, $id): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse("User #{$id} not found", [], 404);
        }

        $user->password_changed_at = now();
        $user->must_change_password = false;
        $user->save();

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
    public function userDelete(Request $request, $id): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse("User #{$id} not found", [], 404);
        }

        // Block self-deletion
        if ($user->id === $request->user()->id) {
            return $this->errorResponse('Cannot delete your own account', [], 422);
        }

        // Block deletion of last superAdmin
        if ($user->can('admin.access') && User::role('superAdmin')->count() <= 1) {
            return $this->errorResponse('Cannot delete the last superAdmin account', [], 422);
        }

        // Block deletion of serviceTechnique
        if ($user->can('module.technical_support.access')) {
            return $this->errorResponse('Cannot delete serviceTechnique accounts', [], 422);
        }

        try {
            $deletedData = [
                'user_id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first() ?? '-',
                'related_data' => [
                    'inscriptions' => $user->etudiant ? $user->etudiant->inscriptions()->count() : 0,
                    'tokens' => $user->tokens()->count(),
                ],
            ];

            // Revoke all Sanctum tokens
            $user->tokens()->delete();

            // Deactivate
            $user->is_active = false;
            $user->save();

            // Soft-delete
            $user->delete();

            Log::info('CLI: user deleted', ['user_id' => $user->id, 'name' => $user->name, 'by' => $request->user()->id]);

            return $this->successResponse($deletedData, "User '{$user->name}' (#{$user->id}) has been deleted");
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

        $validated = $request->validate(['password' => 'required|string|min:8']);

        $user->update([
            'password'             => bcrypt($validated['password']),
            'must_change_password' => false,
            'password_changed_at'  => now(),
        ]);

        Log::info('CLI: password reset', ['user_id' => $user->id, 'by' => $request->user()->id]);

        return $this->successResponse(
            ['name' => $user->name, 'email' => $user->email, 'username' => $user->username],
            'Password reset successfully.'
        );
    }
}
