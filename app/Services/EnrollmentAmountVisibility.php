<?php

namespace App\Services;

class EnrollmentAmountVisibility
{
    public function userCanSeeAmounts($user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can('identity.enrollment_officer')
            && ! $user->hasAnyPermission(['paiements.view', 'comptabilite.access', 'comptabilite.dashboard.view', 'admin.access'])) {
            return false;
        }

        return $user->hasAnyPermission([
            'paiements.view',
            'comptabilite.access',
            'comptabilite.dashboard.view',
            'admin.access',
        ]);
    }

    public function hideAmounts($user): bool
    {
        return ! $this->userCanSeeAmounts($user);
    }

    public function abortIfHidden($user, int $status = 403): void
    {
        abort_if($this->hideAmounts($user), $status);
    }
}
