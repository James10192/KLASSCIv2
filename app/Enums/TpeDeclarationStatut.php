<?php

namespace App\Enums;

/**
 * Statut d'une déclaration TPE (Travail Personnel Étudiant).
 *
 * Source de vérité unique pour `esbtp_tpe_declarations.statut` (VARCHAR).
 * Le statut initial dépend de la Strategy active (AutoValidate / TeacherValidate)
 * elle-même pilotée par le Setting `tpe.validation.enabled`.
 *
 * Cycle de vie :
 *   - AutoValidate (Option 2)  : VALIDE direct → fin
 *   - TeacherValidate (Option 3) : EN_ATTENTE → VALIDE | REJETE
 */
enum TpeDeclarationStatut: string
{
    case VALIDE = 'valide';
    case EN_ATTENTE = 'en_attente';
    case REJETE = 'rejete';

    /**
     * Libellé court affiché dans l'UI (badges, chips, listings).
     */
    public function label(): string
    {
        return match ($this) {
            self::VALIDE => 'Validé',
            self::EN_ATTENTE => 'En attente',
            self::REJETE => 'Rejeté',
        };
    }

    /**
     * Classe CSS du badge (cohérente avec premium-redesign.md).
     *
     * Couleurs sémantiques autorisées car elles portent un sens fonctionnel
     * (statut workflow validation, scanné < 1 sec par l'enseignant).
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::VALIDE => 'tj-badge--success',
            self::EN_ATTENTE => 'tj-badge--warning',
            self::REJETE => 'tj-badge--danger',
        };
    }

    /**
     * Icône Font Awesome pour les badges.
     */
    public function icon(): string
    {
        return match ($this) {
            self::VALIDE => 'fa-check-circle',
            self::EN_ATTENTE => 'fa-clock',
            self::REJETE => 'fa-times-circle',
        };
    }

    /**
     * Une déclaration validée ou rejetée est immuable côté étudiant.
     * Seul EN_ATTENTE est éditable (par l'étudiant) ou en attente d'action prof.
     */
    public function isEditableByStudent(): bool
    {
        return $this === self::EN_ATTENTE;
    }

    /**
     * Une déclaration validée ou rejetée NE peut PLUS être actionnée par le prof.
     * Seul EN_ATTENTE est encore actionnable (validate / reject).
     */
    public function isPendingTeacherAction(): bool
    {
        return $this === self::EN_ATTENTE;
    }

    /**
     * Liste des valeurs string pour validation (`Rule::in(...)`).
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
