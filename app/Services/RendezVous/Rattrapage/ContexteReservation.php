<?php

namespace App\Services\RendezVous\Rattrapage;

use Carbon\CarbonInterface;

/**
 * Ce qu'on sait d'une reservation eligible pour lui choisir un courriel.
 *
 * - `depuis` / `jusqua` : un courriel n'est candidat que s'il est parti apres
 *   la creation de la reservation (a une minute pres) et strictement avant la
 *   plus proche de ces deux bornes : la coupure du suivi, et la creation de
 *   la reservation SUIVANTE du meme dossier (sa confirmation n'est pas celle-ci).
 * - `ancre` : `rdv_invite_at` du dossier, c'est-a-dire le DERNIER envoi au
 *   dossier ; le courriel retenu est le plus recent parti au plus tard a ce
 *   moment-la.
 * - `ancienneEmpreinte` : adresse videe par le nettoyage, retrouvee dans sa
 *   sauvegarde (sha256 seulement), quand elle y figure.
 */
final class ContexteReservation
{
    public function __construct(
        public readonly string $action,
        public readonly ?string $email,
        public readonly ?string $ancienneEmpreinte,
        public readonly ?CarbonInterface $ancre,
        public readonly CarbonInterface $depuis,
        public readonly CarbonInterface $jusqua,
    ) {}
}
