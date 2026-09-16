<?php

namespace App\Domain\Enseignants;

final class VisaSp
{
    public const A_VISER = 'a_viser_sp';

    public const VISE = 'vise';

    public const REFUSE = 'refuse';

    public static function peutPayer(string $statutServiceFait, bool $paiementDefinitifAutorise): bool
    {
        return $statutServiceFait === self::VISE && $paiementDefinitifAutorise;
    }
}
