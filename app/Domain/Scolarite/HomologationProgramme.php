<?php

namespace App\Domain\Scolarite;

final class HomologationProgramme
{
    public static function nEstPasUnAgrementEnseignant(): bool
    {
        return true;
    }

    public static function nEstPasUneAccreditationCames(): bool
    {
        return true;
    }
}
