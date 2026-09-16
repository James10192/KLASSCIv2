<?php

namespace App\Domain\Scolarite;

final class NatureDeNote
{
    public const ZERO = 'zero';

    public const ABSENCE = 'absence';

    public const DISPENSE = 'dispense';

    public const MANQUANTE = 'manquante';

    public const NOTE = 'note';

    public static function classifier(?float $valeur, bool $absent, bool $dispense): string
    {
        if ($dispense) {
            return self::DISPENSE;
        }
        if ($absent) {
            return self::ABSENCE;
        }
        if ($valeur === null) {
            return self::MANQUANTE;
        }
        if ($valeur === 0.0) {
            return self::ZERO;
        }

        return self::NOTE;
    }

    public static function valeurPourMoyenne(string $nature, ?float $valeur, bool $absenceCompteZero): ?float
    {
        return match ($nature) {
            self::NOTE, self::ZERO => $valeur,
            self::ABSENCE => $absenceCompteZero ? 0.0 : null,
            default => null,
        };
    }
}
