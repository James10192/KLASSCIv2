<?php

namespace App\Enums;

enum StatutReservationRdv: string
{
    case Confirmee = 'confirmee';
    case Honoree = 'honoree';
    case Manquee = 'manquee';
    case Annulee = 'annulee';
    case Liberee = 'liberee';

    public function label(): string
    {
        return match ($this) {
            self::Confirmee => 'Confirmé',
            self::Honoree => 'Honoré',
            self::Manquee => 'Non honoré',
            self::Annulee => 'Annulé',
            self::Liberee => 'Libéré',
        };
    }

    /**
     * Libelle d'un filtre de liste, vu du guichet : on cherche les familles
     * « reçues » ou « non venues », pas des rendez-vous « honorés ».
     */
    public function libelleFiltre(): string
    {
        return match ($this) {
            self::Confirmee => 'Confirmés',
            self::Honoree => 'Reçues',
            self::Manquee => 'Non venues',
            self::Annulee => 'Annulés',
            self::Liberee => 'Libérés',
        };
    }

    public function occupeLeCreneau(): bool
    {
        return in_array($this, self::occupants(), true);
    }

    /** @return list<self> */
    public static function occupants(): array
    {
        return [self::Confirmee, self::Honoree, self::Manquee];
    }

    /** @return list<string> */
    public static function valeursOccupantes(): array
    {
        return array_map(static fn (self $s) => $s->value, self::occupants());
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
