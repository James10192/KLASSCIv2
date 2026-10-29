<?php

namespace App\Enums;

/**
 * Statut de la justification d'absence d'un étudiant.
 *
 * Source de vérité unique pour `esbtp_attendances.justification_status` (VARCHAR(20)).
 *
 * Cycle de vie :
 *   - NULL                                  : aucune justification soumise
 *   - PENDING (en attente)                  : étudiant a soumis, admin doit traiter
 *   - APPROVED (validée)                    : admin a approuvé → statut absence = excuse
 *   - REJECTED (rejetée)                    : admin a rejeté → étudiant peut re-soumettre
 *
 * L'étudiant peut re-soumettre uniquement quand REJECTED.
 * L'admin agit uniquement sur PENDING.
 */
enum JustificationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Libellé court affiché dans l'UI (badges, chips, listings).
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::APPROVED => 'Validée',
            self::REJECTED => 'Rejetée',
        };
    }

    /**
     * Classe CSS du badge (namespace ja-* / jap-*).
     *
     * Couleurs sémantiques autorisées car elles portent un sens fonctionnel
     * (statut workflow, scanné < 1 sec par l'utilisateur).
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'ja-badge--warning',
            self::APPROVED => 'ja-badge--success',
            self::REJECTED => 'ja-badge--danger',
        };
    }

    /**
     * Icône Font Awesome pour les badges.
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'fa-clock',
            self::APPROVED => 'fa-check-circle',
            self::REJECTED => 'fa-times-circle',
        };
    }

    /**
     * Une justification rejetée est ré-éditable par l'étudiant (re-soumission).
     * APPROVED est terminal. PENDING est en attente d'action admin (pas d'action étudiant).
     */
    public function isEditableByStudent(): bool
    {
        return $this === self::REJECTED;
    }

    /**
     * Seules les justifications PENDING attendent une action admin.
     */
    public function isPendingTeacherAction(): bool
    {
        return $this === self::PENDING;
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
