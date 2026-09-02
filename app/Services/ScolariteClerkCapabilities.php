<?php

namespace App\Services;

use App\Models\User;

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
        $isClerk = $user->hasPermissionTo('identity.registrar_clerk');
        $isRegistrar = $user->hasPermissionTo('identity.registrar');

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
}
