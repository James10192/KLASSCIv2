<?php

namespace App\Enums;

/**
 * Types d'épreuves UEMOA. Aligné avec ESBTPExamenPlanifie::TYPES et
 * App\Enums\TypeSeance (qui contient EXAMEN/PARTIEL/RATTRAPAGE/SOUTENANCE
 * pour la planification dans l'emploi du temps).
 */
enum TypeExamen: string
{
    case EXAMEN = 'EXAMEN';
    case PARTIEL = 'PARTIEL';
    case RATTRAPAGE = 'RATTRAPAGE';
    case SOUTENANCE = 'SOUTENANCE';

    public function label(): string
    {
        return match ($this) {
            self::EXAMEN => 'Examen terminal',
            self::PARTIEL => 'Partiel (mi-semestre)',
            self::RATTRAPAGE => 'Rattrapage (2ᵉ session)',
            self::SOUTENANCE => 'Soutenance',
        };
    }

    public function badgeClass(): string
    {
        return 'exp-chip--'.strtolower($this->value);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function selectOptions(): array
    {
        $opts = [];
        foreach (self::cases() as $case) {
            $opts[$case->value] = $case->label();
        }
        return $opts;
    }

    public static function labelFor(?string $raw): string
    {
        return self::tryFrom((string) $raw)?->label() ?? (string) $raw;
    }
}
