<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Helpers\SettingsHelper;

/**
 * Les deux seuils du tableau de bord pédagogique, réglés par l'école.
 *
 * Aucun n'est une règle du logiciel : une école relance un enseignant trois
 * jours après un devoir, une autre attend deux semaines ; l'une s'inquiète
 * d'un étudiant présent à 80 %, l'autre à 60 %. Les valeurs de repli ne sont
 * que le point de départ proposé.
 *
 * Les clés sont semées par la migration `add_seuils_de_pilotage_settings` et
 * exposées dans /esbtp/settings (onglet Général).
 */
final class SeuilsDePilotage
{
    public const REGLAGE_RELANCE_JOURS = 'pilotage.relance_notes_apres_jours';
    public const REGLAGE_PRESENCE_MIN = 'pilotage.seuil_presence_pct';

    public const RELANCE_JOURS_REPLI = 7;
    public const PRESENCE_MIN_REPLI = 75;

    /** Jours après une évaluation au-delà desquels une note manquante appelle une relance. */
    public function relanceApresJours(): int
    {
        return $this->entierBorne(self::REGLAGE_RELANCE_JOURS, self::RELANCE_JOURS_REPLI, 0, 90);
    }

    /** Taux de présence en dessous duquel un étudiant est signalé, en pourcentage. */
    public function presenceMinimale(): int
    {
        return $this->entierBorne(self::REGLAGE_PRESENCE_MIN, self::PRESENCE_MIN_REPLI, 1, 100);
    }

    /**
     * Une valeur hors bornes ou illisible retombe sur le repli : un réglage
     * saisi « abc » ne doit pas faire signaler toute l'école.
     */
    private function entierBorne(string $cle, int $repli, int $min, int $max): int
    {
        $brut = SettingsHelper::get($cle, $repli);

        if (! is_numeric($brut)) {
            return $repli;
        }

        $valeur = (int) $brut;

        return $valeur < $min || $valeur > $max ? $repli : $valeur;
    }
}
