<?php

namespace App\Services\Reinscription;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;

/**
 * Le solde qui décide si une réinscription est bloquée. UNE définition, lue par
 * l'écran de réinscription et par Nanan : deux formules différentes faisaient
 * dire « bloquée » à l'une et « possible » à l'autre pour le même élève.
 *
 * C'est un solde NET de l'inscription : ce qui est facturé moins ce qui est
 * validé, tous frais confondus. Un trop-versé sur un frais compense donc un
 * autre frais — c'est la règle de l'écran, gardée telle quelle.
 */
final class SoldeDeReinscription
{
    public static function du(int $inscriptionId): float
    {
        return ESBTPFraisSubscription::dueAmountForInscription($inscriptionId);
    }

    public static function paye(int $inscriptionId): float
    {
        return ESBTPPaiement::netPaidForInscription($inscriptionId);
    }

    public static function solde(int $inscriptionId): float
    {
        return round(self::du($inscriptionId) - self::paye($inscriptionId), 2);
    }
}
