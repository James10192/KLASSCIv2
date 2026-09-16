<?php

namespace App\Domain\Scolarite;

final class ReclamationNotes
{
    public static function peutCorrigerUnBulletinPublie(bool $publie, bool $nouvelleVersion): bool
    {
        return ! $publie || $nouvelleVersion;
    }

    public static function impayeNeBloquePasLesNotes(): bool
    {
        return true;
    }
}
