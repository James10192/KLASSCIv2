<?php

namespace App\Domain\Enseignants;

final class AgrementEnseignant
{
    public static function autoriseNouvelleAffectation(?string $expiration, string $dateCours, string $statut): bool
    {
        if (in_array($statut, ['revoque', 'suspendu'], true)) {
            return false;
        }

        if ($expiration === null || $expiration === '') {
            return true;
        }

        return $dateCours <= $expiration;
    }

    public static function serviceDejaFaitConserveLaDette(bool $serviceFait): bool
    {
        return $serviceFait;
    }
}
