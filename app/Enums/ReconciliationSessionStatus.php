<?php

namespace App\Enums;

enum ReconciliationSessionStatus: string
{
    case DRAFT = 'draft';
    case REVIEW = 'review';
    case APPROVED = 'approved';
    case CLOSED = 'closed';
    case REOPENED = 'reopened';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::REVIEW => 'En revue',
            self::APPROVED => 'Approuvée',
            self::CLOSED => 'Clôturée',
            self::REOPENED => 'Réouverte',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'rec-badge--muted',
            self::REVIEW => 'rec-badge--warning',
            self::APPROVED => 'rec-badge--info',
            self::CLOSED => 'rec-badge--success',
            self::REOPENED => 'rec-badge--danger',
        };
    }

    /**
     * Transitions autorisées (état → états atteignables).
     *
     * @return array<int,self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::REVIEW],
            self::REVIEW => [self::APPROVED, self::DRAFT],
            self::APPROVED => [self::CLOSED, self::REVIEW],
            self::CLOSED => [self::REOPENED],
            self::REOPENED => [self::DRAFT],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this === self::CLOSED;
    }

    public function isModifiable(): bool
    {
        return in_array($this, [self::DRAFT, self::REOPENED], true);
    }

    /**
     * @return array<int,string>
     */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
