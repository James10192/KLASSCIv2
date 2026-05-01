<?php

namespace App\Enums;

/**
 * Régime contractuel d'un enseignant.
 *
 * Source de vérité unique pour les valeurs `regime` côté DB
 * (table `esbtp_teachers`, colonne ENUM).
 */
enum TeacherRegime: string
{
    case Vacataire = 'vacataire';
    case Permanent = 'permanent';
    case Consultant = 'consultant';

    /**
     * Libellé court affiché dans l'UI (radio cards, badges, KPIs).
     */
    public function label(): string
    {
        return match ($this) {
            self::Vacataire => 'Vacataire',
            self::Permanent => 'Permanent',
            self::Consultant => 'Consultant',
        };
    }

    /**
     * Description fonctionnelle affichée sous le libellé dans les radio cards.
     */
    public function description(): string
    {
        return match ($this) {
            self::Vacataire => 'Heure facturée, contrat semestriel',
            self::Permanent => 'Salaire mensuel, charge fixe',
            self::Consultant => 'Mission ponctuelle, expertise',
        };
    }

    /**
     * Icône Font Awesome associée au régime (utilisée dans les radio cards).
     */
    public function icon(): string
    {
        return match ($this) {
            self::Vacataire => 'fa-clock',
            self::Permanent => 'fa-user-tie',
            self::Consultant => 'fa-handshake',
        };
    }

    /**
     * Liste des valeurs string pour validation (Rule::in(...)) ou comparaison brute.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
