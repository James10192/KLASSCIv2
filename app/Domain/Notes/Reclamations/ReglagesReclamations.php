<?php

declare(strict_types=1);

namespace App\Domain\Notes\Reclamations;

use App\Helpers\SettingsHelper;

/**
 * Les deux réglages d'école des réclamations de notes.
 *
 * Semés par la migration `create_esbtp_reclamations_notes_table`, exposés dans
 * /esbtp/settings (onglet Scolarité). Le délai n'est pas une règle du
 * logiciel : une école laisse quinze jours, une autre une semaine.
 */
final class ReglagesReclamations
{
    public const REGLAGE_ACTIF = 'notes.reclamations.enabled';
    public const REGLAGE_DELAI_JOURS = 'notes.reclamations.delai_jours';

    public const DELAI_REPLI = 15;

    public function actives(): bool
    {
        return SettingsHelper::drapeau(self::REGLAGE_ACTIF, true);
    }

    /** Jours, après la dernière écriture de la note, pendant lesquels on peut la contester. */
    public function delaiJours(): int
    {
        $brut = SettingsHelper::get(self::REGLAGE_DELAI_JOURS, self::DELAI_REPLI);

        if (! is_numeric($brut)) {
            return self::DELAI_REPLI;
        }

        $valeur = (int) $brut;

        return $valeur < 1 || $valeur > 365 ? self::DELAI_REPLI : $valeur;
    }
}
