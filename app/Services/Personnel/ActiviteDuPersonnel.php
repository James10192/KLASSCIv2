<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use App\Helpers\SettingsHelper;

/**
 * L'activité du personnel, en faits vérifiables.
 */
final class ActiviteDuPersonnel
{
    public const REGLAGE_ATTENTE_JOURS = 'personnel.paiement_attente_jours';

    public const ATTENTE_JOURS_REPLI = 3;

    /** Jours au-delà desquels un paiement en attente de validation est signalé. */
    public function attenteJours(): int
    {
        $brut = SettingsHelper::get(self::REGLAGE_ATTENTE_JOURS, self::ATTENTE_JOURS_REPLI);

        return is_numeric($brut) && (int) $brut >= 0 && (int) $brut <= 90 ? (int) $brut : self::ATTENTE_JOURS_REPLI;
    }
}
