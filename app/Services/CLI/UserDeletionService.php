<?php

namespace App\Services\CLI;

use App\Exceptions\UserDeletionRejectedException;
use App\Models\User;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

final class UserDeletionService
{
    public function __construct(private SuperAdminLifecycleGuard $lifecycle) {}

    /** @return array{status: int, message: string, data?: array<string, mixed>} */
    public function delete(int $userId, User $actor): array
    {
        return $this->lifecycle->deactivateUser(
            $userId,
            function (User $user) use ($actor): array {
                $data = $this->snapshot($user);
                $this->deactivateAndDelete($user);
                Log::info('CLI: user deleted', ['user_id' => $user->id, 'name' => $user->name, 'by' => $actor->id]);

                return [
                    'status' => 200,
                    'message' => "User '{$user->name}' (#{$user->id}) has been deleted",
                    'data' => $data,
                ];
            },
            authorize: fn (User $user) => $this->authorizeTarget($user, $actor),
        );
    }

    private function authorizeTarget(User $user, User $actor): void
    {
        if ($user->id === $actor->id) {
            throw new UserDeletionRejectedException('Cannot delete your own account');
        }

        if ($user->hasRole('superAdmin') && !$actor->hasRole('superAdmin')) {
            throw new AuthorizationException('Only a superAdmin can delete another superAdmin account');
        }

        if ($user->hasRole('serviceTechnique')) {
            throw new UserDeletionRejectedException('Cannot delete serviceTechnique accounts');
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(User $user): array
    {
        return [
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
    }

    private function deactivateAndDelete(User $user): void
    {
        $user->tokens()->delete();
        $user->is_active = false;
        $user->save();
        $user->delete();
    }

}
