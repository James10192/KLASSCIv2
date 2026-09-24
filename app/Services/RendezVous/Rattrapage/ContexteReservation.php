<?php

namespace App\Services\RendezVous\Rattrapage;

use Carbon\CarbonInterface;

/**
 * Ce qu'on sait d'une reservation eligible pour lui choisir un courriel.
 *
 * - Fenetre : un courriel n'est candidat que s'il est parti au plus tot
 *   TOLERANCE_CREATION_SECONDES avant la creation de la reservation, et
 *   strictement avant `jusqua` : la plus proche de la coupure du suivi et de
 *   la creation de la reservation SUIVANTE du meme dossier.
 * - `aUnePrecedente` : le dossier avait deja une reservation. Un courriel
 *   parti AVANT la creation de celle-ci (dans la tolerance) peut alors etre
 *   celui de la precedente : il est ambigu.
 * - `ancre` : `rdv_invite_at` du dossier, c'est-a-dire le DERNIER envoi au
 *   dossier.
 * - `ancienneEmpreinte` : adresse videe par le nettoyage, retrouvee dans sa
 *   sauvegarde (sha256 seulement), quand elle y figure.
 */
final class ContexteReservation
{
    /** Le courriel part juste apres l'enregistrement de la reservation, a une minute pres. */
    public const TOLERANCE_CREATION_SECONDES = 60;

    public const PAR_ADRESSE = 'adresse';

    public const PAR_SAUVEGARDE = 'sauvegarde';

    public const PAR_DOMAINE = 'domaine';

    public function __construct(
        public readonly string $action,
        public readonly ?string $email,
        public readonly ?string $ancienneEmpreinte,
        public readonly ?CarbonInterface $ancre,
        public readonly CarbonInterface $creeLe,
        public readonly bool $aUnePrecedente,
        public readonly CarbonInterface $jusqua,
    ) {}

    public function depuis(): CarbonInterface
    {
        return $this->creeLe->copy()->subSeconds(self::TOLERANCE_CREATION_SECONDES);
    }

    /** Comment le destinataire du courriel est reconnu pour cette reservation. */
    public function modeDestinataire(): string
    {
        return match (true) {
            $this->email !== null && trim($this->email) !== '' => self::PAR_ADRESSE,
            $this->ancienneEmpreinte !== null => self::PAR_SAUVEGARDE,
            default => self::PAR_DOMAINE,
        };
    }
}
