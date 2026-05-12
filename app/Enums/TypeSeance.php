<?php

namespace App\Enums;

enum TypeSeance: string
{
    case CM      = 'CM';
    case TD      = 'TD';
    case TP      = 'TP';
    case PROJET  = 'PROJET';
    case TPE     = 'TPE';
    case EXAMEN  = 'EXAMEN';
    case AUTRE   = 'AUTRE';

    public function label(): string
    {
        return match($this) {
            self::CM     => 'Cours Magistral',
            self::TD     => 'Travaux Dirigés',
            self::TP     => 'Travaux Pratiques',
            self::PROJET => 'Projet',
            self::TPE    => 'Travail Personnel Étudiant',
            self::EXAMEN => 'Examen',
            self::AUTRE  => 'Autre',
        };
    }

    /** Returns all case values as a plain array (for Rule::in()). */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Map legacy type_seance strings to canonical enum values.
     * Conservative: ambiguous values ('cours') → AUTRE, not CM.
     */
    public static function fromLegacy(?string $raw): self
    {
        if ($raw === null || $raw === '') {
            return self::AUTRE;
        }

        $upper = strtoupper(trim($raw));

        return match($upper) {
            'CM'     => self::CM,
            'TD'     => self::TD,
            'TP'     => self::TP,
            'PROJET' => self::PROJET,
            'TPE'    => self::TPE,
            'EXAMEN' => self::EXAMEN,
            default  => self::AUTRE,
        };
    }

    /** Whether this type counts toward volume horaire tracking (CM/TD/TP). */
    public function isVolumeTracked(): bool
    {
        return in_array($this, [self::CM, self::TD, self::TP], true);
    }

    /** Returns ['VALUE' => 'Label'] array for <x-au-select> :options prop. */
    public static function selectOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
