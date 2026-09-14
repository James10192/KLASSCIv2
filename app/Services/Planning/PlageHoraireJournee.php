<?php

namespace App\Services\Planning;

use App\Helpers\SettingsHelper;

/**
 * La plage horaire d'une journee de cours, reglee par etablissement.
 *
 * Les grilles de disponibilite, la saisie des seances et l'emploi du temps
 * etaient bornes en dur a 18h, et chacun a sa maniere (8h-18h ici, 7h-18h la) :
 * une ecole qui donne ses masters le soir, de 18h a 22h, ne pouvait ni saisir
 * ces seances dans la fenetre ni voir ses enseignants disponibles. L'heure de
 * fin d'une journee de cours est une realite d'ecole, pas une regle du logiciel.
 *
 * Tous ces ecrans lisent desormais cette plage. Les indices des matrices de
 * disponibilite (`[jour][heure - debut]`) en dependent : le serveur et le
 * navigateur doivent partir du meme debut, d'ou `pourLeNavigateur()`.
 */
class PlageHoraireJournee
{
    public const CLE_DEBUT = 'planning.heure_debut';

    public const CLE_FIN = 'planning.heure_fin';

    public const DEBUT_PAR_DEFAUT = 7;

    public const FIN_PAR_DEFAUT = 18;

    /** Heure a laquelle commence le premier creneau (7 → 7h00). */
    public function debut(): int
    {
        return $this->bornes()[0];
    }

    /** Heure a laquelle se termine le dernier creneau (18 → 18h00). */
    public function fin(): int
    {
        return $this->bornes()[1];
    }

    /**
     * Heures de debut des creneaux d'une heure : 7..17 pour 7h-18h.
     *
     * @return list<int>
     */
    public function creneaux(): array
    {
        return range($this->debut(), $this->fin() - 1);
    }

    /**
     * Heures proposees a la saisie, fin comprise : 7..18 pour 7h-18h.
     *
     * @return list<int>
     */
    public function heuresDeSaisie(): array
    {
        return range($this->debut(), $this->fin());
    }

    /** @return array{debut: int, fin: int} */
    public function pourLeNavigateur(): array
    {
        return ['debut' => $this->debut(), 'fin' => $this->fin()];
    }

    /**
     * Un reglage illisible ou incoherent retombe sur la plage par defaut
     * plutot que de produire une grille vide.
     *
     * @return array{0: int, 1: int}
     */
    private function bornes(): array
    {
        $debut = $this->heure(SettingsHelper::get(self::CLE_DEBUT, self::DEBUT_PAR_DEFAUT));
        $fin = $this->heure(SettingsHelper::get(self::CLE_FIN, self::FIN_PAR_DEFAUT));

        if ($debut === null || $fin === null || $debut >= $fin || $fin > 23) {
            return [self::DEBUT_PAR_DEFAUT, self::FIN_PAR_DEFAUT];
        }

        return [$debut, $fin];
    }

    /** Accepte 18, "18" ou "18:00". */
    private function heure(mixed $valeur): ?int
    {
        if (is_int($valeur) || (is_float($valeur) && floor($valeur) === $valeur)) {
            return (int) $valeur;
        }

        if (is_string($valeur) && preg_match('/^\s*(\d{1,2})(?::\d{2})?\s*$/', $valeur, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
