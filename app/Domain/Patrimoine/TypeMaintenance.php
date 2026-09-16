<?php

namespace App\Domain\Patrimoine;

final class TypeMaintenance
{
    public const PREVENTIVE = 'preventive';

    public const REPARATION = 'reparation';

    public const CONTROLE = 'controle_reglementaire';

    public static function fermerUnTicketNEffacePasLesCouts(bool $ticketFerme): bool
    {
        return $ticketFerme;
    }
}
