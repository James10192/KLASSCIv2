<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ScolariteClerkCapabilities
{
    /**
     * Droits pédagogie / inscriptions en plus du pack service scolarité.
     * Planning, volumes horaires et personnel enseignant restent hors pack.
     *
     * @var array<int, string>
     */
    public const PEDAGOGIE = [
        'students.create',
        'students.edit',
        'inscriptions.create',
        'inscriptions.edit',
        'inscriptions.validate',
        'inscriptions.fiche.print',
        'inscriptions.in_kind.mark',
        'inscriptions.candidatures.view',
        'inscriptions.candidatures.process',
        'reinscriptions.demandes.process',
    ];

    /**
     * @var array<int, string>
     */
    public const TEACHERS = [
        'teachers.view',
        'teachers.create',
        'teachers.edit',
        'module.enseignants.access',
    ];

    public function __construct(private readonly TenantScolariteSettings $settings)
    {
    }

    public function grants(User $user, string $ability): bool
    {
        return in_array($ability, $this->abilitiesFor($user), true);
    }

    /**
     * @return array<int, string>
     */
    public function abilitiesFor(User $user): array
    {
        $isClerk = $this->detient($user, 'identity.registrar_clerk');
        $isRegistrar = $this->detient($user, 'identity.registrar');

        $abilities = [];

        if ($isClerk && $this->settings->clerkLmdAccess()) {
            $abilities[] = 'module.lmd.access';
        }

        if ($isClerk && $this->settings->clerkPedagogieAccess()) {
            $abilities = array_merge($abilities, self::PEDAGOGIE);
        }

        if (($isClerk || $isRegistrar) && $this->settings->manageTeachers()) {
            $abilities = array_merge($abilities, self::TEACHERS);
        }

        return $abilities;
    }

    /**
     * Detention d'une permission, sans exiger qu'elle existe deja en base.
     *
     * Ce service est appele depuis un Gate::after, donc a CHAQUE `can()` de
     * l'application. Or `hasPermissionTo()` leve PermissionDoesNotExist quand la
     * permission n'a pas encore ete creee : sur une instance mise a jour avant
     * que `bin/deploy/fix_permissions.php` n'ait tourne, la moindre verification
     * d'acces renvoyait une erreur 500 au lieu d'un refus. Une permission absente
     * signifie simplement que personne ne la detient.
     */
    private function detient(User $user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
