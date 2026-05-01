<?php

namespace App\Enums;

/**
 * Statut administratif d'un enseignant.
 *
 * Source de vérité unique pour les valeurs `status` côté DB
 * (table `esbtp_teachers`, colonne VARCHAR).
 */
enum TeacherStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * Libellé court affiché dans l'UI (badges, toggles).
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Inactive => 'Inactif',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
