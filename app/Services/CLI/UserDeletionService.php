<?php

namespace App\Services\CLI;

use App\Exceptions\UserDeletionRejectedException;
use App\Listeners\AuditPermissionChange;
use App\Models\User;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

final class UserDeletionService
{
    public function __construct(
        private SuperAdminLifecycleGuard $lifecycle,
        private AuditPermissionChange $audit
    ) {}

    /** @return array{status: int, message: string, data?: array<string, mixed>} */
    public function delete(int $userId, User $actor, string $reason): array
    {
        return $this->lifecycle->deactivateUser(
            $userId,
            function (User $user) use ($actor, $reason): array {
                $data = $this->snapshot($user);
                $before = ['is_active' => (bool) $user->is_active, 'deleted_at' => (string) $user->deleted_at];
                $this->deactivateAndDelete($user);
                $this->audit->enregistrerDecisionDeCompte(
                    $user, $before,
                    ['is_active' => false, 'deleted_at' => (string) $user->deleted_at],
                    $actor->id, $reason
                );
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

        // Permission effective de l'acteur, indépendante de son nom de rôle.
        if (! $actor->checkPermissionTo('personnel.users.deactivate', 'web')) {
            throw new AuthorizationException('Permission de désactivation de compte absente.');
        }

        // Le rôle de la CIBLE définit ici un état métier protégé ; jamais
        // un passe-droit de l'auteur. Toute suppression sensible exige le
        // futur workflow de double validation : la CLI refuse sans exception.
        if ($user->getRoleNames()->intersect(['superAdmin', 'serviceTechnique'])->isNotEmpty()) {
            throw new AuthorizationException('Compte sensible : double approbation obligatoire.');
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
            'roles' => $user->getRoleNames()->all(),
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
