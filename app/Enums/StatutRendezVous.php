<?php

namespace App\Enums;

/**
 * Ce qu'un rendez-vous de guichet est devenu.
 *
 * Les quatre etats existent des la premiere version, y compris ceux que rien
 * n'exploite encore. Une reservation gratuite en ligne a un taux d'absence
 * eleve, et c'est `absent` qui permettra un jour de le mesurer ; ajouter la
 * valeur plus tard laisserait une cohorte entiere sans histoire, et il faudrait
 * alors deviner ce que les anciennes lignes signifiaient.
 *
 * `honore` et `absent` sont poses au pointage, par l'agent qui recoit. Ce sont
 * eux, avec `arrive_at` et `termine_at`, qui feront exister la seule donnee de
 * calibration dont ce depot ne dispose pas : combien de temps une famille
 * occupe reellement un guichet. Aucun horodatage existant ne le dit —
 * `traite_at` est pose AVANT le travail, pour reserver la ligne contre le
 * double-clic, et il est remis a null au moindre redepot.
 */
enum StatutRendezVous: string
{
    /** Pris par la famille, pas encore honore. Le seul etat qui occupe une place. */
    case RESERVE = 'reserve';

    /** La famille s'est presentee et a ete recue. */
    case HONORE = 'honore';

    /** Le creneau est passe sans que personne ne se presente. */
    case ABSENT = 'absent';

    /** Annule par la famille ou par l'ecole. Libere la place. */
    case ANNULE = 'annule';

    public function libelle(): string
    {
        return match ($this) {
            self::RESERVE => 'Reserve',
            self::HONORE => 'Honore',
            self::ABSENT => 'Absent',
            self::ANNULE => 'Annule',
        };
    }

    /**
     * Ce statut retient-il une place sur son creneau ?
     *
     * Une seule source pour cette question. La poser a deux endroits — le
     * comptage d'occupation et la liste du jour — finirait par y repondre
     * differemment, et l'ecart se verrait sous la forme d'un creneau annonce
     * libre puis refuse a la reservation.
     */
    public function occupeUnePlace(): bool
    {
        return $this === self::RESERVE;
    }

    /** Un rendez-vous que l'agent peut encore pointer. */
    public function estEnAttente(): bool
    {
        return $this === self::RESERVE;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $cas) => $cas->value, self::cases());
    }

    /** Les statuts qui retiennent une place, pour une clause `whereIn`. */
    public static function occupants(): array
    {
        return array_values(array_map(
            static fn (self $cas) => $cas->value,
            array_filter(self::cases(), static fn (self $cas) => $cas->occupeUnePlace()),
        ));
    }
}
