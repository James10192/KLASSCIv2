<?php

namespace App\Services\Personnel;

use App\Listeners\AuditPermissionChange;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Mutations de rôles sur la base isolée de l'établissement.
 * Les rôles sensibles ne sont lus ici que pour qualifier l'état de la cible,
 * jamais pour autoriser l'acteur.
 */
final class RoleAssignmentService
{
    public function __construct(
        private readonly AuditPermissionChange $audit,
        private readonly UserManagementService $userManagement,
    ) {
    }

    /** @return array{before: array<int,string>, after: array<int,string>} */
    public function change(
        User $actor, int $targetId, string $mode, string $roleName, string $reason,
        ?string $oldRole = null, array $expectedRoles = []
    ): array {
        if (! in_array($mode, ['add', 'remove', 'replace'], true)) {
            throw ValidationException::withMessages(['mode' => 'Opération inconnue.']);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['motif' => 'Motif obligatoire.']);
        }
        if ($mode === 'replace' && (! $oldRole || $expectedRoles === [])) {
            throw ValidationException::withMessages(['expected_roles' => 'Ancien rôle et état préalable obligatoires.']);
        }
        if ($actor->id === $targetId) {
            throw new AuthorizationException('Impossible de modifier ses propres rôles.');
        }

        return DB::transaction(function () use ($actor, $targetId, $mode, $roleName, $reason, $oldRole, $expectedRoles): array {
            // Même verrou d'exclusion que SuperAdminLifecycleGuard.
            Role::query()->where('name', 'superAdmin')->where('guard_name', 'web')->lockForUpdate()->first();
            $operator = User::query()->findOrFail($actor->id);
            $target = User::query()->with('roles')->whereKey($targetId)->lockForUpdate()->firstOrFail();

            // checkPermissionTo ne consulte pas le Gate::before historique.
            // Le contrat HTTP appelle l'ajout « add », mais la permission
            // canonique du registry s'appelle bien « assign ».
            $actionPermission = $mode === 'add' ? 'assign' : $mode;
            if (! $operator->checkPermissionTo('personnel.roles.'.$actionPermission, 'web')) {
                throw new AuthorizationException('Permission de changement de rôle absente.');
            }
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if (! $role) {
                throw ValidationException::withMessages(['role' => 'Rôle absent de cet établissement.']);
            }
            $before = $target->getRoleNames()->sort()->values()->all();
            if (array_intersect($before, ['superAdmin', 'serviceTechnique']) !== []) {
                // La double validation de transformation n'est pas encore livrée.
                throw new AuthorizationException('Compte sensible : approbation distincte obligatoire.');
            }
            // La permission d'ajout/retrait n'accorde pas le contrôle d'une
            // personne plus privilégiée. Conserver l'invariant de la policy
            // UserManagementPolicy pour l'ensemble des points d'entrée.
            if (! $this->userManagement->canManage($operator, $target)) {
                throw new AuthorizationException(
                    'Les permissions de ce compte dépassent votre périmètre de gestion.'
                );
            }
            if ($roleName === 'serviceTechnique') {
                throw new AuthorizationException('Attribution du rôle technique indisponible depuis cette API.');
            }
            if ($roleName === 'superAdmin') {
                // Ajouter superAdmin à un compte existant est une promotion,
                // PAS une création initiale. Le droit de créer un compte
                // ne remplace pas l'approbation indépendante maker-checker.
                // En l'absence du workflow, cette API échoue fermée.
                throw new AuthorizationException(
                    'Promotion Superadministrateur : approbation distincte obligatoire.'
                );
            }
            if ($mode === 'replace') {
                if (collect($expectedRoles)->unique()->sort()->values()->all() !== $before) {
                    throw ValidationException::withMessages(['expected_roles' => 'État périmé, actualisez avant modification.']);
                }
                if (! in_array($oldRole, $before, true)) {
                    throw ValidationException::withMessages(['old_role' => 'Le rôle à remplacer est absent.']);
                }
            }
            if ($mode === 'remove' && ! in_array($roleName, $before, true)) {
                throw ValidationException::withMessages(['role' => 'Rôle non attribué.']);
            }

            // Contrôler aussi les privilèges RETIRÉS : une permission de
            // retrait ne doit pas permettre de dégrader un rôle hors périmètre.
            // Lors d'un remplacement, les deux côtés sont vérifiés.
            $rolesAControler = [$role];
            if ($mode === 'replace') {
                $ancien = Role::query()->where('name', $oldRole)->where('guard_name', 'web')->first();
                if (! $ancien) {
                    throw ValidationException::withMessages(['old_role' => 'Ancien rôle introuvable.']);
                }
                $rolesAControler[] = $ancien;
            }
            foreach ($rolesAControler as $roleControle) {
                foreach ($roleControle->permissions()->pluck('name') as $permission) {
                    if (! $operator->checkPermissionTo($permission, 'web')) {
                        throw new AuthorizationException('Une permission de ce rôle est hors de votre périmètre.');
                    }
                }
            }

            // Le remplacement n'efface aucun autre rôle ni aucun profil.
            if ($mode === 'add' && ! in_array($roleName, $before, true)) {
                $target->assignRole($role);
            } elseif ($mode === 'remove') {
                $target->removeRole($role);
            } elseif ($mode === 'replace' && $oldRole !== $roleName) {
                $target->removeRole($oldRole);
                if (! in_array($roleName, $before, true)) {
                    $target->assignRole($role);
                }
            }
            $after = $target->fresh()->getRoleNames()->sort()->values()->all();
            $this->audit->enregistrerDecisionDeCompte(
                $target, ['roles' => $before], ['roles' => $after], $operator->id, $reason
            );

            return ['before' => $before, 'after' => $after];
        }, 3);
    }
}
