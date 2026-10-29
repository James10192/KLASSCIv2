<?php

namespace App\Services;

/**
 * Relais vers la porte `finances.etudiants.voir` (AuthServiceProvider), pour
 * les appelants qui raisonnent en « masquer les montants ».
 *
 * Ce service portait sa propre liste de permissions, differente de la porte,
 * et y comptait `admin.access` — que detiennent aussi l'enseignant et le
 * coordinateur. Deux listes divergeaient, et la plus large ouvrait les
 * montants a des profils sans aucune permission financiere.
 */
class EnrollmentAmountVisibility
{
    public function userCanSeeAmounts($user): bool
    {
        return (bool) $user?->can('finances.etudiants.voir');
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
