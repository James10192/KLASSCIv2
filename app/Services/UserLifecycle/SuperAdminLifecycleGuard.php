<?php

namespace App\Services\UserLifecycle;

use App\Exceptions\LastActiveSuperAdminException;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class SuperAdminLifecycleGuard
{
    private const TRANSACTION_ATTEMPTS = 3;

    public function deactivateUser(int $userId, Closure $mutation, ?Closure $authorize = null): mixed
    {
        return $this->withLockedUsers([$userId], function (Collection $users) use ($mutation, $authorize): mixed {
            $this->authorizeUsers($users, $authorize);
            $this->assertActiveSuperAdminRemains($users);

            return $mutation($users->firstOrFail());
        });
    }

    public function toggleUser(int $userId, ?Closure $afterUpdate = null, ?Closure $authorize = null): bool
    {
        return $this->withLockedUsers([$userId], function (Collection $users) use ($afterUpdate, $authorize): bool {
            $this->authorizeUsers($users, $authorize);
            $user = $users->firstOrFail();
            if ($user->is_active) {
                $this->assertActiveSuperAdminRemains($users);
            }

            $user->update(['is_active' => !$user->is_active]);
            $afterUpdate?->__invoke($user);

            return (bool) $user->is_active;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function updateUser(
        int $userId,
        array $attributes,
        ?Closure $afterUpdate = null,
        ?Closure $authorize = null,
    ): User
    {
        return $this->withLockedUsers([$userId], function (Collection $users) use ($attributes, $afterUpdate, $authorize): User {
            $this->authorizeUsers($users, $authorize);
            $user = $users->firstOrFail();
            if ($user->is_active && array_key_exists('is_active', $attributes) && !$attributes['is_active']) {
                $this->assertActiveSuperAdminRemains($users);
            }

            $user->update($attributes);
            $afterUpdate?->__invoke($user);

            return $user;
        });
    }

    /** @param array<int, int> $userIds */
    public function deactivateUsers(array $userIds, Closure $mutation, ?Closure $authorize = null): mixed
    {
        return $this->withLockedUsers($userIds, function (Collection $users) use ($mutation, $authorize): mixed {
            $this->authorizeUsers($users, $authorize);
            $this->assertActiveSuperAdminRemains($users);

            return $mutation($users);
        });
    }

    /** @param array<int, int> $userIds */
    public function activateUsers(array $userIds, Closure $mutation, ?Closure $authorize = null): mixed
    {
        return $this->withLockedUsers($userIds, function (Collection $users) use ($mutation, $authorize): mixed {
            $this->authorizeUsers($users, $authorize);

            return $mutation($users);
        });
    }

    private function lockCanonicalRole(): void
    {
        Role::query()
            ->where('name', 'superAdmin')
            ->where('guard_name', 'web')
            ->lockForUpdate()
            ->first();
    }

    /** @param Collection<int, User> $users */
    private function authorizeUsers(Collection $users, ?Closure $authorize): void
    {
        if (!$authorize) {
            return;
        }

        foreach ($users as $user) {
            $authorize($user);
        }
    }

    /** @param array<int, int> $userIds */
    private function withLockedUsers(array $userIds, Closure $mutation): mixed
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        return DB::transaction(function () use ($userIds, $mutation): mixed {
            $this->lockCanonicalRole();
            $users = User::query()
                ->with('roles')
                ->whereKey($userIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($users->count() !== count($userIds)) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(User::class, $userIds);
            }

            return $mutation($users);
        }, self::TRANSACTION_ATTEMPTS);
    }

    /** @param Collection<int, User> $users */
    private function assertActiveSuperAdminRemains(Collection $users): void
    {
        $activeSuperAdminIds = $users
            ->filter(fn (User $user) => $user->is_active && $user->hasRole('superAdmin'))
            ->modelKeys();

        if ($activeSuperAdminIds === []) {
            return;
        }

        $anotherActiveExists = User::role('superAdmin')
            ->where('users.is_active', true)
            ->whereNotIn('users.id', $activeSuperAdminIds)
            ->exists();

        if (!$anotherActiveExists) {
            throw new LastActiveSuperAdminException();
        }
    }
}
